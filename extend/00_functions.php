<?php
/**
 * 공통 헬퍼 함수 (PHP 5.6 ~ 8.x 호환)
 * extend/ 디렉토리는 알파벳 순으로 자동 로드되므로 00_ 접두사로 가장 먼저 로드됩니다.
 */

if (!function_exists('h')) {
    /**
     * HTML 이스케이프 (빠른 출력용)
     */
    function h($v, $flags = ENT_QUOTES, $encoding = 'UTF-8') {
        if ($v === null) return '';
        if (is_array($v) || is_object($v)) return '';
        return htmlspecialchars((string)$v, $flags, $encoding);
    }
}

if (!function_exists('ses')) {
    /**
     * 배열 키 안전 접근 + 타입 변환 (?? 연산자 대체용)
     *
     * @param array  $array   원본 배열 ($_POST, $_GET, $_SESSION 등)
     * @param string $key     배열 키
     * @param mixed  $default 키가 없을 때 반환값
     * @param string $type    'int' | 'string' | 'raw' | 'array' | 'bool' | 'float' | null
     *                       - 'string': trim + sql_escape_string (SQL 안전)
     *                       - 'raw'   : trim 만 적용
     *                       - 'array' : 배열 강제 반환
     */
    function ses($array, $key, $default = null, $type = null) {
        $exists = is_array($array) && array_key_exists($key, $array);
        $val = $exists ? $array[$key] : $default;

        if ($type === null) return $val;

        switch ($type) {
            case 'int':    return (int)$val;
            case 'float':  return (float)$val;
            case 'bool':   return (bool)$val;
            case 'array':  return is_array($val) ? $val : array();
            case 'raw':    return is_string($val) ? trim($val) : $val;
            case 'string':
                if (!is_string($val)) $val = (string)$val;
                $val = trim($val);
                if (function_exists('sql_escape_string')) {
                    $val = sql_escape_string($val);
                }
                return $val;
        }
        return $val;
    }
}

if (!function_exists('table_has_column')) {
    /**
     * 테이블에 컬럼이 존재하는지 검사.
     */
    function table_has_column($table, $column) {
        $table_e  = sql_real_escape_string($table);
        $column_e = sql_real_escape_string($column);
        $row = sql_fetch("show columns from `{$table_e}` like '{$column_e}'");
        return !empty($row);
    }
}

if (!function_exists('alter_table_add_column')) {
    /**
     * 테이블에 컬럼을 안전하게 추가 (이미 있으면 skip).
     *
     * @param string $table      예: $g5['character_table']
     * @param string $column     예: 'ch_search_reset_date'
     * @param string $definition 예: "varchar(8) NOT NULL DEFAULT ''"
     * @param string $after      AFTER 절(선택). 빈 문자열이면 마지막에 추가.
     * @return bool true=추가됨 또는 이미 존재, false=실패
     */
    function alter_table_add_column($table, $column, $definition, $after = '') {
        if (table_has_column($table, $column)) return true;
        $table_e  = sql_real_escape_string($table);
        $column_e = sql_real_escape_string($column);
        $sql = "alter table `{$table_e}` add column `{$column_e}` {$definition}";
        if ($after !== '') {
            $after_e = sql_real_escape_string($after);
            $sql .= " after `{$after_e}`";
        }
        return (bool)sql_query($sql, false);
    }
}

if (!function_exists('alter_table_drop_column')) {
    /**
     * 테이블에서 컬럼을 안전하게 제거 (없으면 skip).
     */
    function alter_table_drop_column($table, $column) {
        if (!table_has_column($table, $column)) return true;
        $table_e  = sql_real_escape_string($table);
        $column_e = sql_real_escape_string($column);
        return (bool)sql_query("alter table `{$table_e}` drop column `{$column_e}`", false);
    }
}

if (!function_exists('migrate_columns')) {
    function migrate_columns($table, $columns, $type = 'add') {
            global $g5;
            if (!isset($g5[$table])) return false;
            if (!is_array($columns) || empty($columns)) return false;
            $tbl_name = sql_real_escape_string($g5[$table]);

            // 테이블의 기존 컬럼 목록을 한 번에 조회
            $existing = array();
            $rs = sql_query(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME   = '{$tbl_name}'",
                false
            );
            if ($rs) {
                while ($row = sql_fetch_array($rs)) {
                    $existing[strtolower($row['COLUMN_NAME'])] = true;
                }
            }

            foreach ($columns as $col => $def) {
                $col = sql_real_escape_string($col);
                if ($type === 'add' && !isset($existing[$col_key])) {
                    sql_query("ALTER TABLE `{$g5[$table]}` ADD COLUMN `{$col}` {$def}", false);
                } else if ($type === 'drop' && isset($existing[$col_key])) {
                    sql_query("ALTER TABLE `{$g5[$table]}` DROP COLUMN `{$col}`", false);
                }
            }
        }
}


if (!function_exists('adm_input_field')) {
    /**
     * 입력 폼 컬럼 1개의 입력 컨트롤을 출력한다. (adm/_partial/form.php · list.php 공용)
     *
     *   $type     : 'form' → name="{col}",      id="{col}"     (단일 레코드)
     *               'list' → name="{col}[{i}]", id="{col}_{i}" (목록형 일괄수정)
     *   $col_name : 컬럼명
     *   $col      : 컬럼 정의 (type/label/options/placeholder/value/attr/html ...)
     *   $row      : 현재 레코드(연관배열)
     *   $i        : list 행 인덱스 (form 은 0)
     *
     * 지원 타입: text/number/url/email/password/date/select/checkbox/
     *            checkbox_multiple/radio/textarea/file/html
     *
     * checkbox_multiple : options(value=>label 또는 [value]) 복수 선택.
     *   저장 시 set_update_form 이 콤마(,)로 implode, 표시 시 explode 하여 checked.
     *   name 은 form='{col}[]', list='{col}[{i}][]' (list 의 행 인덱스 $i 와 충돌 방지).
     */
    function adm_input_field($type, $col_name, $col, $row, $i = 0) {
        $is_list = ($type === 'list');
        $ns      = $is_list ? '['.$i.']' : '';            // name 인덱스 접미사
        $id      = $col_name . ($is_list ? '_'.$i : '');  // id
        $ftype   = isset($col['type']) ? $col['type'] : 'text';
        $val     = array_key_exists($col_name, $row) ? $row[$col_name] : (isset($col['value']) ? $col['value'] : '');
        $ph      = isset($col['placeholder']) ? $col['placeholder'] : '';
        $attr    = isset($col['attr']) ? ' '.$col['attr'] : '';
        $opts    = (isset($col['options']) && is_array($col['options'])) ? $col['options'] : array();
        // [0=>'a',1=>'b'] 는 value 목록. [1=>'거실', 3=>'침실'] 처럼 키가 0..n-1 이 아니면 value=>label.
        // (ca_id 등 숫자 PK 는 PHP 가 키를 int 로 캐스팅하므로 is_int 로는 구분 불가)
        $nopts = count($opts);
        $opt_list = ($nopts === 0) || (array_keys($opts) === range(0, $nopts - 1));

        switch ($ftype) {
            case 'html':
                echo is_callable($col['html']) ? $col['html']($row) : $col['html'];
                break;

            case 'select':
                echo '<select name="'.$col_name.$ns.'" id="'.$id.'" style="width:100%;"'.$attr.'>';
                foreach ($opts as $ok => $ov) {
                    $ovv = $opt_list ? $ov : $ok;
                    $sel = ((string)$val === (string)$ovv) ? ' selected' : '';
                    echo '<option value="'.h($ovv).'"'.$sel.'>'.h($ov).'</option>';
                }
                echo '</select>';
                break;

            case 'checkbox':
                $cv  = isset($col['value']) ? $col['value'] : '1';
                $chk = ((string)$val !== '' && (string)$val !== '0') ? ' checked' : '';
                echo '<input type="checkbox" name="'.$col_name.$ns.'" id="'.$id.'" value="'.h($cv).'"'.$chk.$attr.'>';
                break;

            case 'checkbox_multiple':
                // 복수 선택 — 저장은 콤마(,)로 implode, 표시는 explode 하여 checked
                $sel_vals = ($val === '' || $val === null) ? array() : explode(',', (string)$val);
                $grp = $col_name.$ns.'[]';
                foreach ($opts as $ok => $ov) {
                    $ovv = $opt_list ? $ov : $ok;
                    $chk = in_array((string)$ovv, $sel_vals, true) ? ' checked' : '';
                    echo '<label style="margin-right:8px;"><input type="checkbox" name="'.$grp.'" value="'.h($ovv).'"'.$chk.'> '.h($ov).'</label>';
                }
                break;

            case 'radio':
                foreach ($opts as $ok => $ov) {
                    $ovv = $opt_list ? $ov : $ok;
                    $chk = ((string)$val === (string)$ovv) ? ' checked' : '';
                    echo '<label style="margin-right:8px;"><input type="radio" name="'.$col_name.$ns.'" value="'.h($ovv).'"'.$chk.'> '.h($ov).'</label>';
                }
                break;

            case 'textarea':
                echo '<textarea name="'.$col_name.$ns.'" id="'.$id.'" style="width:100%; height:100px;" placeholder="'.h($ph).'"'.$attr.'>'.get_text($val).'</textarea>';
                break;

            case 'file':
                // 이미지 전용 — 미리보기 + 삭제 체크박스 + 파일 업로드 + URL 입력
                //   name 규칙: 업로드 {col}_file{ns}, 삭제 {col}_del{ns}, URL {col}_url{ns}
                if ($val) {
                    echo '<p style="margin:0 0 4px;"><img src="'.h($val).'" alt="" style="max-width:120px; max-height:120px;"></p>';
                    echo '<label><input type="checkbox" name="'.$col_name.'_del'.$ns.'" value="1"> 등록된 이미지 삭제</label><br>';
                }
                echo '파일 <input type="file" name="'.$col_name.'_file'.$ns.'" id="'.$id.'" accept="image/*"'.$attr.'><br>';
                echo 'URL <input type="text" name="'.$col_name.'_url'.$ns.'" value="" style="width:60%;" placeholder="'.h($ph !== '' ? $ph : '이미지 URL (http:// 또는 /)').'">';
                break;
            case 'color':
                $is_none  = ($val === '' || $val === null);
                $disp_val = $is_none ? '#ffffff' : h($val);
                $none_chk = $is_none ? ' checked' : '';
                $col_dis  = $is_none ? ' disabled' : '';
                echo '<label style="margin-right:6px;"><input type="checkbox" name="'.$col_name.'_none'.$ns.'" id="'.$id.'_none" value="1"'.$none_chk.' onchange="document.getElementById(\''.$id.'\').disabled=this.checked;"> 없음</label>';
                echo '<input type="color" name="'.$col_name.$ns.'" value="'.$disp_val.'" id="'.$id.'" style="width:80px; height:32px;"'.$col_dis.$attr.'>';
                break;

            case 'text':
            case 'number':
            case 'url':
            case 'email':
            case 'password':
            case 'date':
            default:
                echo '<input type="'.$ftype.'" name="'.$col_name.$ns.'" value="'.get_text($val).'" id="'.$id.'" style="width:100%;" placeholder="'.h($ph).'"'.$attr.'>';
                break;
        }
    }
}


if (!function_exists('set_insert_form')) {
    /**
     *   입력 폼 파셜 출력 헬퍼
     *   $type        : 'list' → adm/_partial/list.php (목록형 일괄수정 폼)
     *                  'form' → adm/_partial/form.php (단일 레코드 입력 폼)
     *   $result_list : list 는 행 배열, form 은 편집할 단일 레코드(연관배열)
     *   $hidden      : 검색상태 등 hidden input (호출측에서 구성해 전달)
     *   $cols        : 컬럼 정의(공통 구조) — list.php / form.php 가 공유
     */
    function set_insert_form($type, $form_name, $form_action, $result_list=array(), $hidden=array(), $cols=array(), $hidden_id='', $actions=array('선택수정', '선택삭제')) {
        global $g5, $qstr, $config, $is_admin;
        ob_start();
        include (G5_PATH.'/adm/_partial/'.$type.'.php');
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
}

if (!function_exists('set_update_form')) {
    /**
     * set_insert_form 으로 그린 폼에서 POST 된 값을 받아 DB 에 저장하는 처리 헬퍼.
     *   set_insert_form 과 동일한 $cols 정의(컬럼명 => array('type'=>...))를 공유한다.
     *
     *   $type   : 'form' → 단일 레코드 INSERT/UPDATE (w='' 신규, w='u' 수정)
     *             'list' → 목록형 일괄 처리 (act_button: 선택수정 / 선택삭제)
     *   $table  : 실제 테이블명 (예: $g5['module_map_table'])
     *   $cols   : 컬럼 정의. type 이 'html' 인 컬럼은 출력 전용이므로 저장에서 제외.
     *             file 타입은 이미지 업로드/URL/삭제를 처리한다.
     *   $pk     : 기본키 컬럼명 (예: 'ma_id')
     *   $opts   : array(
     *               'file_path'     => 업로드 저장 디렉토리(절대경로),
     *               'file_url'      => 업로드 파일 웹 URL,
     *               'allowed_ext'   => 허용 확장자(기본 jpg/jpeg/png/gif/webp),
     *               'max_size'      => 최대 바이트(기본 5MB),
     *               'redirect'      => 처리 후 이동 URL,
     *               'delete_tables' => 선택삭제 시 같은 pk 로 함께 지울 추가 테이블 배열,
     *             )
     *
     * file 컬럼 POST name 규칙: 업로드 {col}_file, 삭제 {col}_del, URL {col}_url
     *   (list 타입은 모두 [인덱스] 배열)
     */
    function set_update_form($type, $table, $cols, $pk, $opts = array()) {
        global $g5;

        $file_path = isset($opts['file_path']) ? rtrim($opts['file_path'], '/') : '';
        $file_url  = isset($opts['file_url'])  ? rtrim($opts['file_url'], '/')  : '';
        $allowed   = isset($opts['allowed_ext']) ? $opts['allowed_ext'] : array('jpg','jpeg','png','gif','webp');
        $max_size  = isset($opts['max_size']) ? (int)$opts['max_size'] : (5 * 1024 * 1024);
        $redirect  = isset($opts['redirect']) ? $opts['redirect'] : G5_ADMIN_URL;

        // 저장 대상 컬럼만 추출 (html 출력 전용 컬럼 제외)
        $data_cols = array();
        foreach ($cols as $cname => $cdef) {
            $ctype = isset($cdef['type']) ? $cdef['type'] : 'text';
            if ($ctype === 'html') continue;
            $data_cols[$cname] = $cdef;
        }

        // 로컬 업로드 파일만 삭제 (외부 URL 은 건드리지 않음)
        $unlink_local = function ($url) use ($file_url, $file_path) {
            if (!$url || $file_url === '' || strpos($url, $file_url) !== 0) return;
            $fp = $file_path . '/' . basename($url);
            if (is_file($fp)) {
                if (function_exists('cdn_unlink')) { @cdn_unlink($fp); } else { @unlink($fp); }
            }
        };

        // 이미지 업로드 처리 (index 가 주어지면 배열형 $_FILES)
        $do_upload = function ($field, $pk_val, $idx = null) use ($allowed, $max_size, $file_path, $file_url) {
            if (empty($_FILES[$field])) return null;
            $F = $_FILES[$field];
            if ($idx === null) {
                $name = $F['name']; $tmp = $F['tmp_name']; $err = $F['error']; $size = $F['size'];
            } else {
                if (!isset($F['name'][$idx])) return null;
                $name = $F['name'][$idx]; $tmp = $F['tmp_name'][$idx];
                $err = $F['error'][$idx]; $size = $F['size'][$idx];
            }
            if (!$name || $err !== UPLOAD_ERR_OK || !$tmp) return null;
            if ($size > $max_size) return null;
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) return null;
            // 이미지 MIME 검증 (이미지 외 파일 차단)
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = $finfo ? finfo_file($finfo, $tmp) : '';
                if ($finfo) finfo_close($finfo);
                $allowed_mimes = array('image/png','image/jpeg','image/gif','image/webp');
                if (!in_array($mime, $allowed_mimes, true)) return null;
            }
            if ($file_path === '') return null;
            if (!is_dir($file_path)) @mkdir($file_path, 0755, true);
            $fname = $field . '_' . $pk_val . '_' . date('YmdHis') . '.' . $ext;
            $dest  = $file_path . '/' . $fname;
            if (move_uploaded_file($tmp, $dest)) {
                @chmod($dest, 0644);
                return $file_url . '/' . $fname;
            }
            return null;
        };

        // 단일 file 컬럼 값 계산 — 변경 없으면 null 반환(=기존값 유지)
        $resolve_file = function ($cname, $cdef, $pk_val, $existing, $idx) use ($do_upload, $unlink_local) {
            $del_post = ($idx === null) ? ($_POST[$cname.'_del'] ?? '') : ($_POST[$cname.'_del'][$idx] ?? '');
            if (!empty($del_post)) {
                if (!empty($existing[$cname])) $unlink_local($existing[$cname]);
                return '';
            }
            $up = $do_upload($cname.'_file', $pk_val, $idx);
            if ($up !== null) {
                if (!empty($existing[$cname])) $unlink_local($existing[$cname]);
                return $up;
            }
            $url_post = ($idx === null) ? ($_POST[$cname.'_url'] ?? '') : ($_POST[$cname.'_url'][$idx] ?? '');
            $url_post = trim((string)$url_post);
            if ($url_post !== '') {
                if (preg_match('#^https?://#i', $url_post) || strpos($url_post, '/') === 0) {
                    if (!empty($existing[$cname]) && $existing[$cname] !== $url_post) $unlink_local($existing[$cname]);
                    return $url_post;
                }
            }
            return null; // 변경 없음
        };

        // 일반(비 file) 컬럼 POST 값 계산
        $resolve_value = function ($cname, $cdef, $idx) {
            $ctype = isset($cdef['type']) ? $cdef['type'] : 'text';
            if ($ctype === 'checkbox') {
                $on  = isset($cdef['value'])   ? $cdef['value']   : '1';
                $off = isset($cdef['uncheck']) ? $cdef['uncheck'] : '0';
                $set = ($idx === null) ? isset($_POST[$cname]) : isset($_POST[$cname][$idx]);
                return $set ? $on : $off;
            }
            if ($ctype === 'checkbox_multiple') {
                // 복수 체크박스 → 콤마(,)로 implode. 미선택 시 '' (선택 해제 반영)
                $arr = ($idx === null) ? ($_POST[$cname] ?? array()) : ($_POST[$cname][$idx] ?? array());
                if (!is_array($arr)) $arr = ($arr === '' || $arr === null) ? array() : array($arr);
                return implode(',', $arr);
            }
            if ($ctype === 'color') {
                // '없음' 체크박스가 체크됐으면 빈 문자열 저장
                $none = ($idx === null) ? !empty($_POST[$cname.'_none']) : !empty($_POST[$cname.'_none'][$idx]);
                if ($none) return '';
                $v = ($idx === null) ? ($_POST[$cname] ?? null) : ($_POST[$cname][$idx] ?? null);
                return $v;
            }
            $v = ($idx === null) ? ($_POST[$cname] ?? null) : ($_POST[$cname][$idx] ?? null);
            return $v;
        };

        // ───────────── form 타입 ─────────────
        if ($type === 'form') {
            $w      = $_POST['w'] ?? '';
            $pk_val = isset($_POST[$pk]) ? trim($_POST[$pk]) : '';

            if ($w === '' || $pk_val === '') {
                $mx = sql_fetch("SELECT MAX(`{$pk}`) AS mx FROM `{$table}`");
                $pk_val = (int)$mx['mx'] + 1;
                $is_new = true;
                $existing = array();
            } else {
                $is_new = false;
                $existing = sql_fetch("SELECT * FROM `{$table}` WHERE `{$pk}` = '".sql_real_escape_string($pk_val)."'");
                if (empty($existing[$pk])) alert('존재하지 않는 자료입니다.');
            }

            $sets = array();
            foreach ($data_cols as $cname => $cdef) {
                $ctype = isset($cdef['type']) ? $cdef['type'] : 'text';
                if ($ctype === 'file') {
                    $fv = $resolve_file($cname, $cdef, $pk_val, $existing, null);
                    if ($fv !== null)       $sets[$cname] = $fv;
                    else if ($is_new)       $sets[$cname] = '';
                    continue;
                }
                $v = $resolve_value($cname, $cdef, null);
                if ($v !== null)            $sets[$cname] = $v;
                else if ($is_new)           $sets[$cname] = '';
            }

            $pairs = array();
            foreach ($sets as $c => $v) {
                $pairs[] = "`".sql_real_escape_string($c)."` = '".sql_real_escape_string($v)."'";
            }
            $pair_sql = implode(', ', $pairs);

            if ($is_new) {
                $sql = "INSERT INTO `{$table}` SET `{$pk}` = '".sql_real_escape_string($pk_val)."'".($pair_sql ? ", {$pair_sql}" : "");
                sql_query($sql);
            } else if ($pair_sql) {
                $sql = "UPDATE `{$table}` SET {$pair_sql} WHERE `{$pk}` = '".sql_real_escape_string($pk_val)."'";
                sql_query($sql);
            }

            goto_url($redirect, false);
            return;
        }

        // ───────────── list 타입 ─────────────
        $act = $_POST['act_button'] ?? '';
        if (empty($_POST['chk']) || !is_array($_POST['chk'])) {
            alert(($act !== '' ? $act : '작업')." 하실 항목을 하나 이상 선택하세요.");
        }

        if ($act === '선택삭제') {
            check_token();
            foreach ($_POST['chk'] as $k) {
                $k = (int)$k;
                $pk_val = isset($_POST[$pk][$k]) ? trim($_POST[$pk][$k]) : '';
                if ($pk_val === '') continue;
                $existing = sql_fetch("SELECT * FROM `{$table}` WHERE `{$pk}` = '".sql_real_escape_string($pk_val)."'");
                // file 컬럼의 로컬 파일 정리
                foreach ($data_cols as $cname => $cdef) {
                    if ((isset($cdef['type']) ? $cdef['type'] : '') === 'file' && !empty($existing[$cname])) {
                        $unlink_local($existing[$cname]);
                    }
                }
                sql_query("DELETE FROM `{$table}` WHERE `{$pk}` = '".sql_real_escape_string($pk_val)."'");
                if (!empty($opts['delete_tables']) && is_array($opts['delete_tables'])) {
                    foreach ($opts['delete_tables'] as $dt) {
                        sql_query("DELETE FROM `{$dt}` WHERE `{$pk}` = '".sql_real_escape_string($pk_val)."'", false);
                    }
                }
            }
        } else { // 선택수정 (기본)
            foreach ($_POST['chk'] as $k) {
                $k = (int)$k;
                $pk_val = isset($_POST[$pk][$k]) ? trim($_POST[$pk][$k]) : '';
                if ($pk_val === '') continue;
                $existing = sql_fetch("SELECT * FROM `{$table}` WHERE `{$pk}` = '".sql_real_escape_string($pk_val)."'");

                $sets = array();
                foreach ($data_cols as $cname => $cdef) {
                    $ctype = isset($cdef['type']) ? $cdef['type'] : 'text';
                    if ($ctype === 'file') {
                        $fv = $resolve_file($cname, $cdef, $pk_val, $existing, $k);
                        if ($fv !== null) $sets[$cname] = $fv;
                        continue;
                    }
                    $v = $resolve_value($cname, $cdef, $k);
                    if ($v !== null) $sets[$cname] = $v;
                }

                $pairs = array();
                foreach ($sets as $c => $v) {
                    $pairs[] = "`".sql_real_escape_string($c)."` = '".sql_real_escape_string($v)."'";
                }
                if ($pairs) {
                    $sql = "UPDATE `{$table}` SET ".implode(', ', $pairs)." WHERE `{$pk}` = '".sql_real_escape_string($pk_val)."'";
                    sql_query($sql);
                }
            }
        }

        goto_url($redirect, false);
    }
}