<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 회원가입/회원정보 수정 폼 헬퍼 (avo8.0)
 *
 * - 닉네임 길이/문자 종류 검증
 * - 생년 모드 (full / year_month / year_only)
 * - 추가 입력폼 (cf_register_extra_fields)
 * - 항목별 도움말 (cf_register_help)
 */

if (!function_exists('register_nick_min')) {
    function register_nick_min() { global $config; return (int)($config['cf_register_nick_min'] ?? 2); }
}
if (!function_exists('register_nick_max')) {
    function register_nick_max() { global $config; return (int)($config['cf_register_nick_max'] ?? 20); }
}
if (!function_exists('register_nick_filter')) {
    function register_nick_filter() { global $config; return $config['cf_register_nick_filter'] ?? ''; }
}
if (!function_exists('register_birth_mode')) {
    function register_birth_mode() { global $config; $m = $config['cf_register_birth_mode'] ?? 'year_only'; if (in_array($m, array('full','year_month'), true)) $m = 'year_only'; return in_array($m, array('year_only','adult_check','none'), true) ? $m : 'year_only'; }
}

/**
 * 닉네임 검증. 통과 = '', 실패 = 오류 메시지
 */
if (!function_exists('register_validate_nick')) {
    function register_validate_nick($nick) {
        $nick = trim((string)$nick);
        $min  = register_nick_min();
        $max  = register_nick_max();
        $len  = function_exists('mb_strlen') ? mb_strlen($nick, 'UTF-8') : strlen($nick);

        if ($min > 0 && $len < $min) return "닉네임은 최소 {$min}자 이상이어야 합니다.";
        if ($max > 0 && $len > $max) return "닉네임은 최대 {$max}자까지 입력 가능합니다.";

        $f = register_nick_filter();
        if ($f === 'ko') {
            if (!preg_match('/^[\x{AC00}-\x{D7A3}\x{3131}-\x{318E}\s]+$/u', $nick)) {
                return '닉네임은 한글만 입력할 수 있습니다.';
            }
        } elseif ($f === 'koen') {
            if (!preg_match('/^[\x{AC00}-\x{D7A3}\x{3131}-\x{318E}A-Za-z\s]+$/u', $nick)) {
                return '닉네임은 한글, 영문만 입력할 수 있습니다.';
            }
        }
        return '';
    }
}

/**
 * 추가 입력 항목으로 쓸 수 없는 member_table 코어·여분 컬럼.
 * 관리자 저장이 코드에 mb_ 를 붙이므로 level → mb_level 이 되어 권한을 덮어쓸 수 있다.
 */
if (!function_exists('register_extra_reserved_codes')) {
    function register_extra_reserved_codes() {
        $r = array(
            'mb_no', 'mb_id', 'ch_id', 'mb_password', 'mb_password2', 'mb_name', 'mb_nick',
            'mb_level', 'mb_sex', 'mb_birth', 'mb_point', 'mb_today_login', 'mb_login_ip',
            'mb_datetime', 'mb_ip', 'mb_leave_date', 'mb_intercept_date', 'mb_memo',
            'mb_memo_call', 'mb_board_call', 'mb_board_link', 'mb_error_cnt',
            'mb_error_content', 'mb_time_override', 'mb_mode',
        );
        for ($i = 1; $i <= 10; $i++) $r[] = 'mb_'.$i;
        return $r;
    }
}

/**
 * 추가 입력 항목 한 줄 파싱. type 에 select(a||b) 처럼 | 가 들어도 깨지지 않게
 * 앞은 code|label, 끝은 required, 가운데가 type 이다.
 * @return array|null ['code','label','type_raw','required']
 */
if (!function_exists('register_extra_parse_line')) {
    function register_extra_parse_line($line) {
        $line = trim((string)$line);
        if ($line === '' || $line[0] === '#') return null;
        $parts = explode('|', $line);
        if (count($parts) < 2) return null;
        $code = trim($parts[0] ?? '');
        $label = trim($parts[1] ?? '');
        if ($code === '' || $label === '') return null;
        $req = 0;
        $type_raw = 'text';
        if (count($parts) >= 4) {
            $req = ((int)end($parts) === 1) ? 1 : 0;
            $type_raw = trim(implode('|', array_slice($parts, 2, -1)));
        } elseif (count($parts) === 3) {
            $maybe = trim($parts[2]);
            if ($maybe === '0' || $maybe === '1') $req = (int)$maybe;
            else $type_raw = $maybe;
        }
        return array('code'=>$code, 'label'=>$label, 'type_raw'=>$type_raw, 'required'=>$req);
    }
}

/**
 * cf_register_extra_fields 파싱.
 * 형식: code|label|type|required(0/1)
 *   - select 타입은 type 부분에 select(opt1||opt2||opt3) 처럼 옵션 부착
 * @return array of ['code','label','type','required','options']
 */
if (!function_exists('register_extra_fields')) {
    function register_extra_fields() {
        global $config;
        $raw = trim((string)($config['cf_register_extra_fields'] ?? ''));
        if ($raw === '') return array();
        $reserved = array_fill_keys(register_extra_reserved_codes(), true);
        $out = array();
        $lines = preg_split('/\r\n|\r|\n/', $raw);
        foreach ($lines as $line) {
            $parsed = register_extra_parse_line($line);
            if (!$parsed) continue;
            $code = $parsed['code'];
            $label = $parsed['label'];
            $type_raw = $parsed['type_raw'];
            $req = $parsed['required'];
            if (isset($reserved[strtolower($code)])) continue;
            $opts = array();
            $type = $type_raw;
            if (preg_match('/^select\s*\((.*)\)$/i', $type_raw, $m)) {
                $type = 'select';
                $opts = array_filter(array_map('trim', explode('||', $m[1])), function($v){ return $v !== ''; });
                $opts = array_values($opts);
            } elseif (!in_array($type_raw, array('text','textarea','select'), true)) {
                $type = 'text';
            }
            $out[] = array('code'=>$code, 'label'=>$label, 'type'=>$type, 'required'=>$req, 'options'=>$opts);
        }
        return $out;
    }
}

/**
 * 추가 입력 항목 POST 검증. 실패 시 alert 후 중단.
 */
if (!function_exists('register_extra_validate')) {
    function register_extra_validate() {
        $fields = register_extra_fields();
        if (empty($fields)) return;
        $reserved = array_fill_keys(register_extra_reserved_codes(), true);
        foreach ($fields as $f) {
            $code = $f['code'];
            if (!preg_match('/^mb_[a-z0-9_]+$/i', $code)) continue;
            if (isset($reserved[strtolower($code)])) continue;
            $raw = isset($_POST[$code]) ? trim(stripslashes((string)$_POST[$code])) : '';
            $raw = clean_xss_tags($raw);
            if (!empty($f['required']) && $raw === '') {
                alert($f['label'].' 항목을 입력해 주십시오.', '', true, true);
            }
            if ($f['type'] === 'select' && $raw !== '' && !in_array($raw, $f['options'], true)) {
                alert($f['label'].' 항목의 값이 올바르지 않습니다.', '', true, true);
            }
        }
    }
}

/**
 * 추가 입력 항목 POST 를 member_table 에 저장.
 * 예약 컬럼은 건너뛴다. 컬럼별 UPDATE 로 한 필드 실패가 나머지를 죽이지 않는다.
 */
if (!function_exists('register_extra_save')) {
    function register_extra_save($mb_id) {
        global $g5;
        if (function_exists('register_extra_validate')) {
            register_extra_validate();
        }
        $mb_id_sql = preg_replace('/[^a-zA-Z0-9_]/', '', stripslashes((string)$mb_id));
        if ($mb_id_sql === '' || empty($g5['member_table'])) return;

        $fields = register_extra_fields();
        if (empty($fields)) return;
        $reserved = array_fill_keys(register_extra_reserved_codes(), true);

        foreach ($fields as $f) {
            $code = $f['code'];
            if (!preg_match('/^mb_[a-z0-9_]+$/i', $code)) continue;
            if (isset($reserved[strtolower($code)])) continue;

            $raw = isset($_POST[$code]) ? trim(stripslashes((string)$_POST[$code])) : '';
            $raw = clean_xss_tags($raw);

            $esc = sql_real_escape_string($raw);
            $ok = sql_query("UPDATE `{$g5['member_table']}` SET `{$code}` = '{$esc}' WHERE mb_id = '{$mb_id_sql}'", false);
            if ($ok === false) {
                error_log('register_extra_save: failed to update '.$code.' for '.$mb_id_sql);
            }
        }
    }
}

/**
 * 항목별 도움말 맵 반환.
 * 형식: 필드코드|도움말
 */
if (!function_exists('register_field_help_map')) {
    function register_field_help_map() {
        global $config;
        static $cache = null;
        if ($cache !== null) return $cache;
        $raw = trim((string)($config['cf_register_help'] ?? ''));
        $cache = array();
        if ($raw === '') return $cache;
        $lines = preg_split('/\r\n|\r|\n/', $raw);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            $pos = strpos($line, '|');
            if ($pos === false) continue;
            $code = trim(substr($line, 0, $pos));
            $help = trim(substr($line, $pos + 1));
            if ($code !== '' && $help !== '') $cache[$code] = $help;
        }
        return $cache;
    }
}
if (!function_exists('register_field_help')) {
    function register_field_help($code) {
        $m = register_field_help_map();
        return $m[$code] ?? '';
    }
}

/**
 * 생년 입력 박스 렌더 (echo).
 * @param string $value 기존 값 (YYYY-MM-DD 또는 YYYY 등)
 */
if (!function_exists('register_birth_input')) {
    function register_birth_input($value, $required = true) {
        $mode = register_birth_mode();
        $value = trim((string)$value);
        $req = $required ? 'required' : '';
        $reqcls = $required ? 'required' : '';

        // 분해
        $y = ''; $m = ''; $d = '';
        if (preg_match('/^(\d{4})(?:-(\d{1,2}))?(?:-(\d{1,2}))?/', $value, $mm)) {
            $y = $mm[1] ?? ''; $m = $mm[2] ?? ''; $d = $mm[3] ?? '';
        } elseif (preg_match('/^\d+$/', $value)) {
            $y = $value;
        }

        if ($mode === 'none') {
            // 입력받지 않음 — 기존 값만 hidden 으로 보존 (행 자체는 스킨에서 숨김)
            echo '<input type="hidden" name="mb_birth" value="'.htmlspecialchars($value, ENT_QUOTES).'">';
            return;
        }
        if ($mode === 'adult_check') {
            // "성인입니다" 체크박스 — 체크 시 mb_birth='2000-01-01' 자동 세팅
            $checked = ($value !== '') ? 'checked' : '';
            echo '<label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;">';
            echo '<input type="checkbox" name="mb_adult_check" value="1" '.$checked.' '.$req.'>';
            echo '<span>커뮤니티 연령제한에 저촉되지 않습니다.</span>';
            echo '</label>';
            return;
        }
        if ($mode === 'year_only') {
            echo '<input type="number" name="mb_birth" value="'.htmlspecialchars($y, ENT_QUOTES).'" '.$req.' class="frm_input '.$reqcls.'" size="6" min="1900" max="2099" placeholder="YYYY">';
            return;
        }
        if ($mode === 'year_month') {
            $ym = $y && $m ? $y.'-'.str_pad($m,2,'0',STR_PAD_LEFT) : '';
            echo '<input type="month" name="mb_birth" value="'.htmlspecialchars($ym, ENT_QUOTES).'" '.$req.' class="frm_input '.$reqcls.'" placeholder="YYYY-MM">';
            return;
        }
        // full
        $ymd = $y ? $y.'-'.str_pad($m?:'01',2,'0',STR_PAD_LEFT).'-'.str_pad($d?:'01',2,'0',STR_PAD_LEFT) : '';
        echo '<input type="date" name="mb_birth" value="'.htmlspecialchars($ymd, ENT_QUOTES).'" '.$req.' class="frm_input '.$reqcls.'">';
    }
}

/**
 * 추가 폼 필드 렌더 (echo). $member 에서 기존 값 자동 로드.
 */
if (!function_exists('register_extra_render')) {
    function register_extra_render($member = array()) {
        $fields = register_extra_fields();
        if (empty($fields)) return;
        echo '<table class="member-form theme-form"><colgroup><col style="width:110px;"><col></colgroup><tbody>';
        foreach ($fields as $f) {
            $code = $f['code'];
            $val  = isset($member[$code]) ? (string)$member[$code] : '';
            $req  = !empty($f['required']) ? 'required' : '';
            $reqcls = !empty($f['required']) ? 'required' : '';
            $help = register_field_help($code);
            echo '<tr><th scope="row">'.htmlspecialchars($f['label'], ENT_QUOTES).(!empty($f['required']) ? ' <span style="color:#e74c3c;">*</span>' : '').'</th><td>';
            if ($f['type'] === 'textarea') {
                echo '<textarea name="'.htmlspecialchars($code, ENT_QUOTES).'" '.$req.' class="frm_input '.$reqcls.'" style="width:100%;" rows="3">'.htmlspecialchars($val, ENT_QUOTES).'</textarea>';
            } elseif ($f['type'] === 'select') {
                echo '<select name="'.htmlspecialchars($code, ENT_QUOTES).'" '.$req.' class="frm_input '.$reqcls.'">';
                $has_val = ($val !== '' && in_array($val, $f['options'], true));
                if (empty($req)) {
                    echo '<option value="">선택</option>';
                } else {
                    echo '<option value="" disabled'.($has_val ? '' : ' selected').'>선택</option>';
                }
                foreach ($f['options'] as $opt) {
                    $sel = ($opt === $val) ? 'selected' : '';
                    echo '<option value="'.htmlspecialchars($opt, ENT_QUOTES).'" '.$sel.'>'.htmlspecialchars($opt, ENT_QUOTES).'</option>';
                }
                echo '</select>';
            } else {
                echo '<input type="text" name="'.htmlspecialchars($code, ENT_QUOTES).'" value="'.htmlspecialchars($val, ENT_QUOTES).'" '.$req.' class="frm_input '.$reqcls.'" maxlength="255">';
            }
            if ($help !== '') echo '<div class="reg-help" style="font-size:12px;color:#888;margin-top:3px;">'.htmlspecialchars($help, ENT_QUOTES).'</div>';
            echo '</td></tr>';
        }
        echo '</tbody></table>';
    }
}
