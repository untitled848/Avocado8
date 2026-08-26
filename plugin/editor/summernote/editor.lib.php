<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

function editor_html($id, $content, $is_dhtml_editor=true)
{
    global $config, $w, $board;
    global $editor_width, $editor_height;
    static $js = true;

    if ($is_dhtml_editor && $content && !$w && (isset($board['bo_insert_content']) && !empty($board['bo_insert_content']))) {
        if (preg_match('/\r|\n/', $content) && $content === strip_tags($content, '<a><strong><b>')) {
            $content = nl2br($content);
        }
    }

    $width  = isset($editor_width)  ? $editor_width  : '100%';
    $height = isset($editor_height) ? $editor_height : '300px';

    $editor_url = G5_EDITOR_URL . '/' . $config['cf_editor'];

    $html = '';
    $html .= '<span class="sound_only">웹에디터 시작</span>';

    if ($is_dhtml_editor) {
        if ($js) {
            // 관리자는 jQuery 1.8.3이 로드되지만 Summernote는 1.9+ 필요 → 1.12.3으로 업그레이드
            if (defined('G5_IS_ADMIN')) {
                $html .= "\n" . '<script src="' . G5_JS_URL . '/jquery-1.12.3.min.js"></script>';
            }
            $html .= "\n" . '<link rel="stylesheet" href="' . $editor_url . '/summernote-lite.min.css">';
            $html .= "\n" . '<script src="' . $editor_url . '/summernote-lite.min.js"></script>';
            $html .= "\n" . '<script src="' . $editor_url . '/summernote-ko-KR.min.js"></script>';
            $html .= "\n<script>var g5_summernote_nonce = '" . ft_nonce_create('summernote') . "';</script>";
            $js = false;
        }

        $html .= "\n" . '<textarea id="' . $id . '" name="' . $id . '" style="display:none;">' . $content . '</textarea>';
        $html .= '<script>
(function($){
    $(function(){
        $("#' . $id . '").summernote({
            lang: "ko-KR",
            width: "' . $width . '",
            height: "' . $height . '",
            toolbar: [
                ["style", ["style"]],
                ["font", ["bold", "underline", "clear"]],
                ["fontname", ["fontname"]],
                ["color", ["color"]],
                ["para", ["ul", "ol", "paragraph"]],
                ["table", ["table"]],
                ["insert", ["link", "picture", "hr"]],
                ["view", ["fullscreen", "codeview", "help"]]
            ]
        });
    });
})(jQuery);
</script>';
    } else {
        $html .= "\n" . '<textarea id="' . $id . '" name="' . $id . '" style="width:' . $width . ';height:' . $height . ';" maxlength="65536">' . $content . '</textarea>';
    }

    $html .= "\n" . '<span class="sound_only">웹 에디터 끝</span>';
    return $html;
}

// textarea 로 값을 넘긴다. javascript 반드시 필요
function get_editor_js($id, $is_dhtml_editor=true)
{
    if ($is_dhtml_editor) {
        return "document.getElementById('{$id}').value = jQuery('#{$id}').summernote('code');\n";
    } else {
        return "var {$id}_editor = document.getElementById('{$id}');\n";
    }
}

// textarea 의 값이 비어 있는지 검사
function chk_editor_js($id, $is_dhtml_editor=true)
{
    if ($is_dhtml_editor) {
        return "if (jQuery('#{$id}').summernote('isEmpty')) { alert('내용을 입력해 주십시오.'); jQuery('#{$id}').summernote('focus'); return false; }\n";
    } else {
        return "if (!document.getElementById('{$id}').value) { alert('내용을 입력해 주십시오.'); document.getElementById('{$id}').focus(); return false; }\n";
    }
}

/*
 * Nonce Utility (공통 보안 토큰)
 */
if (!defined('FT_NONCE_UNIQUE_KEY'))
    define('FT_NONCE_UNIQUE_KEY', sha1($_SERVER['SERVER_SOFTWARE'] . G5_MYSQL_USER . session_id() . G5_TABLE_PREFIX));

if (!defined('FT_NONCE_SESSION_KEY'))
    define('FT_NONCE_SESSION_KEY', substr(md5(FT_NONCE_UNIQUE_KEY), 5));

if (!defined('FT_NONCE_DURATION'))
    define('FT_NONCE_DURATION', 60 * 60);

if (!defined('FT_NONCE_KEY'))
    define('FT_NONCE_KEY', '_nonce');

if (!function_exists('ft_nonce_create_query_string')) {
    function ft_nonce_create_query_string($action = '', $user = '') {
        return FT_NONCE_KEY . '=' . ft_nonce_create($action, $user);
    }
}

if (!function_exists('ft_get_secret_key')) {
    function ft_get_secret_key($secret) {
        return md5(FT_NONCE_UNIQUE_KEY . $secret);
    }
}

if (!function_exists('ft_nonce_create')) {
    function ft_nonce_create($action = '', $user = '', $timeoutSeconds = FT_NONCE_DURATION) {
        $secret  = ft_get_secret_key($action . $user);
        $salt    = ft_nonce_generate_hash();
        $time    = time();
        $maxTime = $time + $timeoutSeconds;
        $nonce   = $salt . '|' . $maxTime . '|' . sha1($salt . $secret . $maxTime);
        set_session('nonce_' . FT_NONCE_SESSION_KEY, $nonce);
        return $nonce;
    }
}

if (!function_exists('ft_nonce_is_valid')) {
    function ft_nonce_is_valid($nonce, $action = '', $user = '') {
        $secret = ft_get_secret_key($action . $user);
        if (!is_string($nonce)) return false;
        $a = explode('|', $nonce);
        if (count($a) != 3) return false;
        list($salt, $maxTime, $hash) = $a;
        $maxTime = intval($maxTime);
        if (sha1($salt . $secret . $maxTime) !== $hash) return false;
        if (time() > $maxTime) return false;
        $stored = get_session('nonce_' . FT_NONCE_SESSION_KEY);
        return $stored === $nonce;
    }
}

if (!function_exists('ft_nonce_generate_hash')) {
    function ft_nonce_generate_hash() {
        $length = 10;
        $chars  = '1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
        $ll     = strlen($chars) - 1;
        $o      = '';
        while (strlen($o) < $length) {
            $o .= $chars[rand(0, $ll)];
        }
        return $o;
    }
}
