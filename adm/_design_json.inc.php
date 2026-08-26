<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 디자인 설정 JSON 임포트/익스포트 공통 헬퍼.
 * 전체(css_config) / 세계관(world_config) / 문서(content_design_config) 가 공유한다.
 */
if (!isset($DESIGN_JSON_COLS) || !is_array($DESIGN_JSON_COLS)) {
    $DESIGN_JSON_COLS = array(
        'cs_value', 'cs_descript',
        'cs_etc_1', 'cs_etc_2', 'cs_etc_3', 'cs_etc_4', 'cs_etc_5',
        'cs_etc_6', 'cs_etc_7', 'cs_etc_8', 'cs_etc_9', 'cs_etc_10'
    );
}

if (!function_exists('design_json_format_label')) {
    function design_json_format_label($format)
    {
        $labels = array(
            'timeline-design'         => '전체 디자인 설정',
            'timeline-world-design'   => '세계관 디자인 설정',
            'timeline-content-design' => '문서 디자인 설정',
        );
        return isset($labels[$format]) ? $labels[$format] : '디자인 설정';
    }
}

if (!function_exists('design_json_parse_upload')) {
    function design_json_parse_upload($expected_format = 'timeline-design')
    {
        if (empty($_FILES['json_file']['tmp_name']) || !is_uploaded_file($_FILES['json_file']['tmp_name'])) {
            alert('JSON 파일을 선택하세요.');
        }
        if (!empty($_FILES['json_file']['error']) && $_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
            alert('파일 업로드에 실패했습니다.');
        }
        if ((int)$_FILES['json_file']['size'] > 2 * 1024 * 1024) {
            alert('파일이 너무 큽니다. (최대 2MB)');
        }

        $raw = file_get_contents($_FILES['json_file']['tmp_name']);
        if ($raw === false || $raw === '') alert('파일을 읽을 수 없습니다.');
        if (substr($raw, 0, 3) === "\xEF\xBB\xBF") $raw = substr($raw, 3);

        $data = json_decode($raw, true);
        if (!is_array($data)) alert('올바른 JSON 파일이 아닙니다.');

        $label = design_json_format_label($expected_format);
        if (isset($data['format'])) {
            if ($data['format'] !== $expected_format) {
                alert('이 파일은 '.$label.' JSON이 아닙니다.');
            }
        } else if ($expected_format !== 'timeline-design') {
            // 구버전 전체 디자인 JSON 만 format 생략을 허용한다.
            alert('이 파일은 '.$label.' JSON이 아닙니다.');
        }

        $settings = array();
        if (isset($data['settings']) && is_array($data['settings'])) {
            $settings = $data['settings'];
        }
        if (empty($settings)) alert('가져올 디자인 설정(settings)이 없습니다.');

        return array($data, $settings);
    }
}

if (!function_exists('design_json_apply')) {
    function design_json_apply($table, $pd_id, $settings)
    {
        global $DESIGN_JSON_COLS;
        $table = (string)$table;
        $pd_id = (int)$pd_id;
        if ($table === '' || $pd_id <= 0 || !is_array($settings)) return 0;

        $n = 0;
        foreach ($settings as $cs_name => $vals) {
            $cs_name = trim((string)$cs_name);
            if ($cs_name === '' || !preg_match('/^[A-Za-z0-9_]{1,80}$/', $cs_name)) continue;
            if (!is_array($vals)) continue;

            $name_esc = sql_escape_string($cs_name);
            $exist = sql_fetch("SELECT cs_id FROM `{$table}` WHERE cs_name = '{$name_esc}' AND pd_id = '{$pd_id}'", false);

            $sets = array();
            foreach ($DESIGN_JSON_COLS as $c) {
                $v = '';
                if (isset($vals[$c]) && !is_array($vals[$c]) && !is_object($vals[$c])) {
                    $v = (string)$vals[$c];
                }
                $sets[] = "`{$c}` = '".sql_escape_string($v)."'";
            }

            if (!empty($exist['cs_id'])) {
                sql_query("UPDATE `{$table}` SET ".implode(',', $sets)." WHERE cs_id = '".(int)$exist['cs_id']."'", false);
            } else {
                sql_query("INSERT INTO `{$table}` SET pd_id = '{$pd_id}', cs_name = '{$name_esc}', ".implode(',', $sets), false);
            }

            $n++;
            if ($n >= 500) break;
        }
        return $n;
    }
}

if (!function_exists('design_json_collect')) {
    function design_json_collect($table, $pd_id)
    {
        global $DESIGN_JSON_COLS;
        $settings = array();
        $pd_id = (int)$pd_id;
        $r = sql_query("SELECT * FROM `{$table}` WHERE pd_id = '{$pd_id}' ORDER BY cs_id ASC", false);
        if ($r) {
            while ($row = sql_fetch_array($r)) {
                $key = isset($row['cs_name']) ? $row['cs_name'] : '';
                if ($key === '') continue;
                $item = array();
                foreach ($DESIGN_JSON_COLS as $c) {
                    $item[$c] = isset($row[$c]) ? $row[$c] : '';
                }
                $settings[$key] = $item;
            }
        }
        return $settings;
    }
}

if (!function_exists('design_json_download')) {
    function design_json_download($payload, $filename)
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false) alert('JSON 생성에 실패했습니다.');

        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', (string)$filename);
        if ($filename === '') $filename = 'design.json';

        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        echo $json;
        exit;
    }
}
