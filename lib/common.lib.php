<?php
if (!defined('_GNUBOARD_')) exit;
include_once(dirname(__FILE__) .'/pbkdf2.compat.php');


// 마이크로 타임을 얻어 계산 형식으로 만듦
function get_microtime()
{
    return microtime(true);
}

// 한페이지에 보여줄 행, 현재페이지, 총페이지수, URL
function get_paging($write_pages, $cur_page, $total_page, $url, $add = '')
{
    if (!$write_pages) $write_pages = 5;

    $url = preg_replace('#&amp;page=[0-9]*#', '', $url) . '&amp;page=';

    $str = '';

    if ($cur_page > 1) {
        $str .= '<a href="'.$url.'1'.$add.'" class="pg_page pg_control pg_start">처음</a>'.PHP_EOL;
    }

    $start_page = (((int)(($cur_page - 1) / $write_pages)) * $write_pages) + 1;
    $end_page = $start_page + $write_pages - 1;

    if ($end_page >= $total_page) {
        $end_page = $total_page;
    }

    if ($start_page > 1) {
        $str .= '<a href="'.$url.($start_page - 1).$add.'" class="pg_page pg_control pg_prev">이전</a>'.PHP_EOL;
    }

    if ($total_page > 1) {
        for ($k = $start_page; $k <= $end_page; $k++) {
            $str .= '<a href="'.$url.$k.$add.'" class="pg_page">'.$k.'</a>'.PHP_EOL;
        }
    }

    if ($total_page > $end_page) {
        $str .= '<a href="'.$url.($end_page + 1).$add.'" class="pg_page pg_control pg_next">다음</a>'.PHP_EOL;
    }

    if ($cur_page < $total_page) {
        $str .= '<a href="'.$url.$total_page.$add.'" class="pg_page pg_control pg_end">맨끝</a>'.PHP_EOL;
    }

    if ($str) {
        return '<nav class="pg_wrap"><span class="pg">'.$str.'</span></nav>';
    }

    return '';
}

// 페이징 코드의 <nav><span> 태그 다음에 코드를 삽입
function page_insertbefore($paging_html, $insert_html)
{
    if (!$paging_html) {
        $paging_html = '<nav class="pg_wrap"><span class="pg"></span></nav>';
    }

    return preg_replace('/^(<nav[^>]*><span[^>]*>)/', '$1'.$insert_html.PHP_EOL, $paging_html);
}

// 페이징 코드의 </span></nav> 태그 이전에 코드를 삽입
function page_insertafter($paging_html, $insert_html)
{
    if (!$paging_html) {
        $paging_html = '<nav class="pg_wrap"><span class="pg"></span></nav>';
    }

    if (preg_match('#'.PHP_EOL.'</span></nav>#', $paging_html)) {
        $php_eol = '';
    } else {
        $php_eol = PHP_EOL;
    }

    return preg_replace('#(</span></nav>)$#', $php_eol.$insert_html.'$1', $paging_html);
}

// 변수 또는 배열의 이름과 값을 얻어냄. print_r() 함수의 변형 (디버그 용도)
function print_r2($var)
{
    ob_start();
    print_r($var);
    $str = ob_get_contents();
    ob_end_clean();
    $str = str_replace(' ', '&nbsp;', $str);
    echo nl2br("<span style='font-family:Tahoma, 굴림; font-size:9pt;'>".$str.'</span>');
}

// 메타태그/자바스크립트를 이용한 URL 이동 (header location 대체)
function goto_url($url)
{
    $url = str_replace('&amp;', '&', $url);

    // common.php 최상단 G5_FORCE_HTTP 스위치: https → http 강제 변환
    if (defined('G5_FORCE_HTTP') && G5_FORCE_HTTP) {
        $url = preg_replace('#^https://#i', 'http://', $url);
    }

    if (!headers_sent()) {
        header('Location: '.$url);
    } else {
        echo '<script>location.replace("'.$url.'");</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url='.$url.'"></noscript>';
    }

    exit;
}

// 세션변수 생성 (PHP 8용)
function set_session($session_name, $value)
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    $_SESSION[$session_name] = $value;
}

// 세션변수값 얻음
function get_session($session_name)
{
    return isset($_SESSION[$session_name]) ? $_SESSION[$session_name] : '';
}

// 쿠키변수 생성
function set_cookie($cookie_name, $value, $expire)
{
    $domain = defined('G5_COOKIE_DOMAIN') ? G5_COOKIE_DOMAIN : '';

    setcookie(
        md5($cookie_name),
        base64_encode($value),
        time() + (int)$expire,
        '/',
        $domain
    );
}

// 쿠키변수값 얻음
function get_cookie($cookie_name)
{
    $cookie = md5($cookie_name);

    if (isset($_COOKIE[$cookie])) {
        return base64_decode($_COOKIE[$cookie]);
    }

    return '';
}

// 경고메세지를 경고창으로
function alert($msg = '', $url = '', $error = true, $post = false)
{
    global $g5, $config, $member, $is_admin;

    if (!$msg) {
        $msg = '올바른 방법으로 이용해 주십시오.';
    }

    $header = isset($g5['title']) ? $g5['title'] : '';

    include_once(G5_BBS_PATH.'/alert.php');
    exit;
}

// 경고메세지 출력후 창을 닫음
function alert_close($msg, $error = true)
{
    global $g5;

    $header = isset($g5['title']) ? $g5['title'] : '';

    include_once(G5_BBS_PATH.'/alert_close.php');
    exit;
}

// confirm 창
function confirm($msg, $url1 = '', $url2 = '', $url3 = '')
{
    global $g5;

    if (!$msg) {
        alert('올바른 방법으로 이용해 주십시오.');
    }

    if (!trim($url1) || !trim($url2)) {
        alert('$url1 과 $url2 를 지정해 주세요.');
    }

    if (!$url3) {
        $url3 = isset($_SERVER['HTTP_REFERER']) ? clean_xss_tags($_SERVER['HTTP_REFERER']) : '';
    }

    $msg = str_replace("\\n", "<br>", $msg);

    $header = isset($g5['title']) ? $g5['title'] : '';

    include_once(G5_BBS_PATH.'/confirm.php');
    exit;
}

// URL 내 텍스트 자동 링크
function url_auto_link($str)
{
    global $config;

    $target = isset($config['cf_link_target']) ? $config['cf_link_target'] : '_blank';

    $str = str_replace(
        array("&lt;", "&gt;", "&amp;", "&quot;", "&nbsp;", "&#039;"),
        array("\t_lt_\t", "\t_gt_\t", "&", "\"", "\t_nbsp_\t", "'"),
        $str
    );

    $str = preg_replace(
        "/([^(href=\"?'?)|(src=\"?'?)]|\(|^)((http|https|ftp|telnet|news|mms):\/\/[a-zA-Z0-9\.-]+\.[가-힣\xA1-\xFEa-zA-Z0-9\.:&#=_\?\/~\+%@;\-\|\,\(\)]+)/i",
        "\\1<A HREF=\"\\2\" TARGET=\"{$target}\">\\2</A>",
        $str
    );

    $str = preg_replace(
        "/(^|[\"'\s(])(www\.[^\"'\s()]+)/i",
        "\\1<A HREF=\"http://\\2\" TARGET=\"{$target}\">\\2</A>",
        $str
    );

    $str = preg_replace(
        "/[0-9a-z_-]+@[a-z0-9._-]{4,}/i",
        "<a href=\"mailto:\\0\">\\0</a>",
        $str
    );

    $str = str_replace(
        array("\t_nbsp_\t", "\t_lt_\t", "\t_gt_\t", "'"),
        array("&nbsp;", "&lt;", "&gt;", "&#039;"),
        $str
    );

    return $str;
}

// url에 http:// 를 붙인다
function set_http($url)
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    if (!preg_match("/^(http|https|ftp|telnet|news|mms):\/\//i", $url)) {
        $url = 'http://'.$url;
    }

    return $url;
}


// 파일 용량 숫자를 보기 좋은 형식으로 변환
function get_filesize($size)
{
    $size = (int)$size;

    if ($size >= 1048576) {
        return number_format($size / 1048576, 1).'M';
    } else if ($size >= 1024) {
        return number_format($size / 1024, 1).'K';
    } else if ($size > 0) {
        return number_format($size, 0).'byte';
    }

    return '0byte';
}

// 게시글에 첨부된 파일 정보를 배열로 반환
function get_file($bo_table, $wr_id)
{
    global $g5, $qstr;

    $file = array();
    $file['count'] = 0;

    if (!isset($g5['board_file_table']) || !$bo_table || !$wr_id) {
        return $file;
    }

    // bo_table / wr_id 기본 방어
    if (function_exists('sql_real_escape_string')) {
        $bo_table_sql = sql_real_escape_string($bo_table);
    } else {
        $bo_table_sql = addslashes($bo_table);
    }
    $wr_id_sql = (int)$wr_id;

    $sql = "select * from {$g5['board_file_table']} where bo_table = '{$bo_table_sql}' and wr_id = '{$wr_id_sql}' order by bf_no";
    $result = sql_query($sql);

    // $qstr 미정의 방어
    $qstr_str = isset($qstr) ? $qstr : '';
    if ($qstr_str !== '' && $qstr_str[0] != '&') {
        $qstr_str = '&amp;'.$qstr_str;
    }

    while ($row = sql_fetch_array($result)) {
        $no = (int)$row['bf_no'];

        $file[$no] = array();
        $file[$no]['href'] = G5_BBS_URL."/download.php?bo_table={$bo_table_sql}&amp;wr_id={$wr_id_sql}&amp;no={$no}{$qstr_str}";
        $file[$no]['download'] = (int)$row['bf_download'];
        $file[$no]['path'] = function_exists('cdn_data_url') ? cdn_data_url('file/'.$bo_table_sql) : (G5_DATA_URL.'/file/'.$bo_table_sql);
        $file[$no]['size'] = get_filesize($row['bf_filesize']);
        $file[$no]['datetime'] = $row['bf_datetime'];
        $file[$no]['source'] = addslashes($row['bf_source']);
        $file[$no]['bf_content'] = $row['bf_content'];
        $file[$no]['content'] = get_text($row['bf_content']);
        $file[$no]['view'] = view_file_link(
            $row['bf_file'],
            $row['bf_width'],
            $row['bf_height'],
            $file[$no]['content']
        );
        $file[$no]['file'] = $row['bf_file'];
        $file[$no]['image_width'] = $row['bf_width'] ? (int)$row['bf_width'] : 640;
        $file[$no]['image_height'] = $row['bf_height'] ? (int)$row['bf_height'] : 480;
        $file[$no]['image_type'] = $row['bf_type'];

        if (function_exists('mdl_apply_filters')) {
            $file[$no] = mdl_apply_filters('board_file_row', $file[$no], $row, $bo_table, $wr_id);
        }

        $file['count']++;
    }

    return $file;
}

// 폴더의 용량 (하위 폴더 비포함, 주어진 $dir 경로 사용)
function get_dirsize($dir)
{
    $size = 0;

    if (!is_dir($dir)) {
        return 0;
    }

    $dh = opendir($dir);
    if ($dh === false) {
        return 0;
    }

    while (($entry = readdir($dh)) !== false) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $path = $dir.'/'.$entry;

        if (is_file($path)) {
            $filesize = @filesize($path);
            if ($filesize !== false) {
                $size += $filesize;
            }
        }
    }

    closedir($dh);

    return $size;
}


/*************************************************************************
**
**  그누보드 관련 함수 모음
**
*************************************************************************/
// 게시물 정보($write_row)를 출력하기 위하여 $list로 가공된 정보를 복사 및 가공
function get_list($write_row, $board, $skin_url, $subject_len = 40)
{
    global $g5, $config, $qstr, $page;

    // 배열 전체 복사
    $list = $write_row;
    unset($write_row);

    // 공지글 여부
    $bo_notice = isset($board['bo_notice']) ? trim($board['bo_notice']) : '';
    $board_notice = $bo_notice !== '' ? array_map('trim', explode(',', $bo_notice)) : array();
    $list['is_notice'] = in_array($list['wr_id'], $board_notice);

    // 제목
    if ($subject_len) {
        $list['subject'] = conv_subject($list['wr_subject'], $subject_len, '…');
    } else {
        $len = isset($board['bo_subject_len']) ? (int)$board['bo_subject_len'] : 40;
        $list['subject'] = conv_subject($list['wr_subject'], $len, '…');
    }

    // 목록 내용 미리보기
    $use_list_content = !empty($board['bo_use_list_content']);
    $is_mmb = isset($board['bo_type']) && $board['bo_type'] === 'mmb';

    if ($use_list_content || $is_mmb) {
        $html = 0;
        if (isset($list['wr_option']) && strstr($list['wr_option'], 'html1')) {
            $html = 1;
        } else if (isset($list['wr_option']) && strstr($list['wr_option'], 'html2')) {
            $html = 2;
        }
        $list['content'] = conv_content($list['wr_content'], $html);
    }

    // 댓글 수
    $list['comment_cnt'] = '';
    if (!empty($list['wr_comment'])) {
        $list['comment_cnt'] = '<span class="cnt_cmt">'.$list['wr_comment'].'</span>';
    }

    // 날짜/시간 표시
    $wr_datetime = isset($list['wr_datetime']) ? $list['wr_datetime'] : '';
    $list['datetime'] = substr($wr_datetime, 0, 10);
    $list['datetime2'] = $wr_datetime;

    if ($list['datetime'] === G5_TIME_YMD) {
        $list['datetime2'] = substr($wr_datetime, 11, 5);
    } else {
        $list['datetime2'] = substr($wr_datetime, 5, 5);
    }

    // 마지막 수정 시간
    $wr_last = isset($list['wr_last']) ? $list['wr_last'] : $wr_datetime;
    $list['last'] = substr($wr_last, 0, 10);
    $list['last2'] = $wr_last;

    if ($list['last'] === G5_TIME_YMD) {
        $list['last2'] = substr($wr_last, 11, 5);
    } else {
        $list['last2'] = substr($wr_last, 5, 5);
    }

    // 작성자 이름
    $cut_len = isset($config['cf_cut_name']) ? (int)$config['cf_cut_name'] : 10;
    $tmp_name = get_text(cut_str($list['wr_name'], $cut_len));
    $tmp_name2 = cut_str($list['wr_name'], $cut_len);

    if (!empty($board['bo_use_sideview'])) {
        $list['name'] = get_sideview($list['mb_id'], $tmp_name2);
    } else {
        $cls = !empty($list['mb_id']) ? 'sv_member' : 'sv_guest';
        $list['name'] = '<span class="'.$cls.'">'.$tmp_name.'</span>';
    }

    // 답글 들여쓰기
    $reply = isset($list['wr_reply']) ? $list['wr_reply'] : '';
    $list['reply'] = strlen($reply) * 10;

    // 아이콘: 답변
    $list['icon_reply'] = '';
    if ($list['reply']) {
        $list['icon_reply'] = '<img src="'.$skin_url.'/img/icon_reply.gif" style="margin-left:'.$list['reply'].'px;" alt="답변글">';
    }

    // 아이콘: 링크
    $list['icon_link'] = '';
    if (!empty($list['wr_link1']) || !empty($list['wr_link2'])) {
        $list['icon_link'] = '<img src="'.$skin_url.'/img/icon_link.gif" alt="관련링크">';
    }

    // 분류 링크
    $ca_name = isset($list['ca_name']) ? $list['ca_name'] : '';
    $list['ca_name_href'] = G5_BBS_URL.'/board.php?bo_table='.$board['bo_table'].'&amp;sca='.urlencode($ca_name);

    // 글/댓글 링크
    $q = isset($qstr) ? $qstr : '';
    $list['href'] = G5_BBS_URL.'/board.php?bo_table='.$board['bo_table'].'&amp;wr_id='.$list['wr_id'].$q;
    $list['comment_href'] = $list['href'];

    // 아이콘: 새글
    $list['icon_new'] = '';
    if (!empty($board['bo_new'])) {
        $new_limit = G5_SERVER_TIME - ((int)$board['bo_new'] * 3600);
        if ($wr_datetime >= date('Y-m-d H:i:s', $new_limit)) {
            $list['icon_new'] = '<img src="'.$skin_url.'/img/icon_new.gif" alt="새글">';
        }
    }

    // 아이콘: 인기글
    $list['icon_hot'] = '';
    if (!empty($board['bo_hot']) && isset($list['wr_hit']) && $list['wr_hit'] >= $board['bo_hot']) {
        $list['icon_hot'] = '<img src="'.$skin_url.'/img/icon_hot.gif" alt="인기글">';
    }

    // 아이콘: 비밀글
    $list['icon_secret'] = '';
    if (isset($list['wr_option']) && strstr($list['wr_option'], 'secret')) {
        $list['icon_secret'] = '<img src="'.$skin_url.'/img/icon_secret.gif" alt="비밀글">';
    }

    // 링크 정보
    for ($i = 1; $i <= G5_LINK_COUNT; $i++) {
        $link = isset($list["wr_link{$i}"]) ? $list["wr_link{$i}"] : '';
        $list['link'][$i] = set_http(get_text($link));
        $list['link_href'][$i] = G5_BBS_URL.'/link.php?bo_table='.$board['bo_table'].'&amp;wr_id='.$list['wr_id'].'&amp;no='.$i.$q;
        $list['link_hit'][$i] = isset($list["wr_link{$i}_hit"]) ? (int)$list["wr_link{$i}_hit"] : 0;
    }

    // 첨부파일
    if (!empty($board['bo_use_list_file']) || (!empty($list['wr_file']) && $subject_len == 255)) {
        $list['file'] = get_file($board['bo_table'], $list['wr_id']);
    } else {
        $list['file'] = array('count' => isset($list['wr_file']) ? (int)$list['wr_file'] : 0);
    }

    // 아이콘: 파일
    $list['icon_file'] = '';
    if (!empty($list['file']['count'])) {
        $list['icon_file'] = '<img src="'.$skin_url.'/img/icon_file.gif" alt="첨부파일">';
    }

    return $list;
}

// get_list 의 alias
function get_view($write_row, $board, $skin_url)
{
    return get_list($write_row, $board, $skin_url, 255);
}

// 검색어 하이라이트
function search_font($stx, $str)
{
    if (!trim($stx)) {
        return $str;
    }

    $src = array('/', '|');
    $dst = array('\/', '\|');

    $s = explode(' ', $stx);

    $pattern = '';
    $bar = '';

    for ($m = 0; $m < count($s); $m++) {
        $w = trim($s[$m]);
        if ($w === '') continue;

        $tmp = quotemeta($w);
        $tmp = str_replace($src, $dst, $tmp);
        $pattern .= $bar.$tmp."(?![^<]*>)";
        $bar = "|";
    }

    if ($pattern === '') {
        return $str;
    }

    return preg_replace("/($pattern)/i", '<b class="sch_word">$1</b>', $str);
}

// 제목 변환
function conv_subject($subject, $len, $suffix = '')
{
    $result = get_text(cut_str($subject, $len, $suffix));
    if (function_exists('mdl_apply_filters')) {
        $result = mdl_apply_filters('conv_subject', $result, $subject, $len);
    }
    return $result;
}

// 내용 변환
function conv_content($content, $html, $filter = true)
{
    if ($html) {
        $source = array("//");
        $target = array("");

        if ($html == 2) {
            $source[] = "/\n/";
            $target[] = "<br/>";
        }

        // table 태그 짝 맞추기
        $lower = strtolower($content);
        $begin_cnt = substr_count($lower, '<table');
        $end_cnt = substr_count($lower, '</table');

        for ($i = $end_cnt; $i < $begin_cnt; $i++) {
            $content .= '</table>';
        }

        $content = preg_replace($source, $target, $content);

        if ($filter) {
            $content = html_purifier($content);
        }
    } else {
        $content = html_symbol($content);
        $content = str_replace("  ", "&nbsp; ", $content);
        $content = str_replace("\n ", "\n&nbsp;", $content);
        $content = get_text($content, 1);
        $content = url_auto_link($content);
    }

    // CDN 토큰 [DATA_URL] 치환 (cdn 모듈 비활성이어도 안전하게 G5_DATA_URL 로 폴백)
    if (function_exists('cdn_resolve_content')) {
        $content = cdn_resolve_content($content);
    }

    if (function_exists('mdl_apply_filters')) {
        $content = mdl_apply_filters('conv_content', $content, $html);
    }

    // 게시판 이모티콘: 현재 컨텍스트에 $board 가 있고 bo_use_emoticon=1 이면 본문/댓글에 자동 적용.
    if (function_exists('emote_ev') && !empty($GLOBALS['board']) && !empty($GLOBALS['board']['bo_use_emoticon'])) {
        $content = emote_ev($content);
    }

    return $content;
}

// HTMLPurifier 래퍼
function html_purifier($html)
{
    $file = @file(G5_PLUGIN_PATH.'/htmlpurifier/safeiframe.txt');
    $domains = array();

    if (is_array($file)) {
        foreach ($file as $domain) {
            if (preg_match("/^#/", $domain)) {
                continue;
            }
            $domain = trim($domain);
            if ($domain) {
                $domains[] = $domain;
            }
        }
    }

    if (!empty($_SERVER['HTTP_HOST'])) {
        $domains[] = $_SERVER['HTTP_HOST'].'/';
    }

    $safeiframe = implode('|', $domains);

    include_once(G5_PLUGIN_PATH.'/htmlpurifier/HTMLPurifier.standalone.php');

    $config = HTMLPurifier_Config::createDefault();
    $config->set('Cache.SerializerPath', G5_DATA_PATH.'/cache');
    $config->set('HTML.SafeEmbed', false);
    $config->set('HTML.SafeObject', false);
    $config->set('Output.FlashCompat', false);
    $config->set('HTML.SafeIframe', true);
    $config->set('URI.SafeIframeRegexp', '%^(https?:)?//('.$safeiframe.')%');
    $config->set('Attr.AllowedFrameTargets', array('_blank'));

    $purifier = new HTMLPurifier($config);
    return $purifier->purify($html);
}

// 검색 SQL 생성
function get_sql_search($search_ca_name, $search_field, $search_text, $search_operator = 'and')
{
    $str = '';

    if ($search_ca_name) {
        $search_ca_name = addslashes($search_ca_name);
        $str = " ca_name = '{$search_ca_name}' ";
    }

    $search_text = trim(stripslashes(strip_tags($search_text)));

    if ($search_text === '') {
        return $str ? $str : '0';
    }

    if ($str) {
        $str .= ' and ';
    }

    $s = explode(' ', $search_text);

    $tmp = explode(',', trim($search_field));
    $field = explode('||', isset($tmp[0]) ? $tmp[0] : '');
    $not_comment = isset($tmp[1]) ? $tmp[1] : '';

    $str .= '(';
    $op1 = '';

    for ($i = 0; $i < count($s); $i++) {
        $search_str = trim($s[$i]);
        if ($search_str === '') continue;

        $str .= $op1.'(';
        $op2 = '';

        for ($k = 0; $k < count($field); $k++) {
            if ($field[$k] === '') continue;

            if (!preg_match('/^[\w\,\|]+$/', $field[$k])) {
                $field[$k] = 'wr_subject';
            }

            $f = $field[$k];
            $str .= $op2;

            switch ($f) {
                case 'mb_id':
                case 'wr_name':
                    $str .= " {$f} = '{$search_str}' ";
                    break;
                case 'wr_hit':
                case 'wr_good':
                case 'wr_nogood':
                    $str .= " {$f} >= '{$search_str}' ";
                    break;
                case 'wr_num':
                    $str .= " {$f} = ".((-1) * (int)$search_str);
                    break;
                case 'wr_ip':
                case 'wr_password':
                    $str .= ' 1=0 ';
                    break;
                default:
                    if (preg_match('/[a-zA-Z]/', $search_str)) {
                        $str .= "INSTR(LOWER({$f}), LOWER('{$search_str}'))";
                    } else {
                        $str .= "INSTR({$f}, '{$search_str}')";
                    }
                    break;
            }

            $op2 = ' or ';
        }

        $str .= ')';
        $op1 = ' '.$search_operator.' ';
    }

    $str .= ' ) ';

    if ($not_comment) {
        $str .= " and wr_is_comment = '0' ";
    }

    if (function_exists('mdl_apply_filters')) {
        $str = mdl_apply_filters('sql_search_where', $str, $search_ca_name, $search_field, $search_text);
    }

    return $str;
}

// 게시판 테이블에서 하나의 행을 읽음
function get_write($write_table, $wr_id)
{
    $wr_id = (int)$wr_id;
    return sql_fetch("select * from {$write_table} where wr_id = '{$wr_id}'");
}

// 다음 글 번호
function get_next_num($table)
{
    $row = sql_fetch("select min(wr_num) as min_wr_num from {$table}");
    return isset($row['min_wr_num']) ? (int)$row['min_wr_num'] - 1 : -1;
}

// 그룹 정보
function get_group($gr_id)
{
    global $g5;
    $gr_id = addslashes($gr_id);
    return sql_fetch("select * from {$g5['group_table']} where gr_id = '{$gr_id}'");
}

// 회원 정보
function get_member($mb_id, $fields = '*')
{
    global $g5;
    $mb_id = trim($mb_id);
    return sql_fetch("select {$fields} from {$g5['member_table']} where mb_id = '{$mb_id}'");
}

// 정렬 링크
function subject_sort_link($col, $query_string = '', $flag = 'asc')
{
    global $sst, $sod, $sfl, $stx, $page, $sca;

    $q1 = "sst={$col}";
    if ($flag == 'asc') {
        $q2 = 'sod=asc';
        if ($sst == $col && $sod == 'asc') {
            $q2 = 'sod=desc';
        }
    } else {
        $q2 = 'sod=desc';
        if ($sst == $col && $sod == 'desc') {
            $q2 = 'sod=asc';
        }
    }

    $arr_query = array();
    if ($query_string !== '') $arr_query[] = $query_string;
    $arr_query[] = $q1;
    $arr_query[] = $q2;
    $arr_query[] = 'sfl='.$sfl;
    $arr_query[] = 'stx='.$stx;
    $arr_query[] = 'sca='.$sca;
    $arr_query[] = 'page='.$page;

    $qstr = implode('&amp;', $arr_query);

    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    return '<a href="'.$script.'?'.$qstr.'">';
}

// 관리자 정보
function get_admin($admin = 'super', $fields = '*')
{
    global $config, $group, $board, $g5;

    $mb = null;
    $is = false;

    if ($admin == 'board' && !empty($board['bo_admin'])) {
        $mb = sql_fetch("select {$fields} from {$g5['member_table']} where mb_id = '{$board['bo_admin']}' limit 1");
        $is = true;
    }

    if (($is && (!$mb || !$mb['mb_id'])) || $admin == 'group') {
        if (!empty($group['gr_admin'])) {
            $mb = sql_fetch("select {$fields} from {$g5['member_table']} where mb_id = '{$group['gr_admin']}' limit 1");
            $is = true;
        }
    }

    if (($is && (!$mb || !$mb['mb_id'])) || $admin == 'super') {
        $mb = sql_fetch("select {$fields} from {$g5['member_table']} where mb_id = '{$config['cf_admin']}' limit 1");
    }

    return $mb;
}

// 관리자인가?
/**
 * 관리자 역할. 빈 문자열이면 비관리자.
 * 권한 분기는 반환값(전역 $is_admin)의 참으로 본다. === 'super' 만 보지 말 것.
 * @return string ''|'super'|'group'|'board'
 */
function is_admin($mb_id)
{
    global $config, $group, $board;

    if (!$mb_id) {
        return '';
    }

    $mb = get_member($mb_id);
    if ($config['cf_admin'] == $mb_id || (isset($mb['mb_level']) && $mb['mb_level'] == 10)) {
        return 'super';
    }
    if (isset($group['gr_admin']) && $group['gr_admin'] == $mb_id) {
        return 'group';
    }
    if (isset($board['bo_admin']) && $board['bo_admin'] == $mb_id) {
        return 'board';
    }

    return '';
}

// 카테고리 옵션
function get_category_option($bo_table = '', $ca_name = '')
{
    global $board, $is_admin;

    $list = isset($board['bo_category_list']) ? $board['bo_category_list'] : '';
    if ($is_admin) {
        $list .= '|공지';
    }

    $categories = explode('|', $list);

    $str = '';
    for ($i = 0; $i < count($categories); $i++) {
        $category = trim($categories[$i]);
        if ($category === '') continue;

        $str .= '<option value="'.$categories[$i].'"';
        if ($category == $ca_name) {
            $str .= ' selected="selected"';
        }
        $str .= '>'.$categories[$i]."</option>\n";
    }

    return $str;
}

// 카테고리 리스트
function get_category_list($bo_table = '', $ca_name = '')
{
    global $board;

    $list = isset($board['bo_category_list']) ? $board['bo_category_list'] : '';
    $categories = explode('|', $list);

    $str = '';
    for ($i = 0; $i < count($categories); $i++) {
        $category = trim($categories[$i]);
        if ($category === '') continue;

        $str .= '<li';
        if ($category == $ca_name) {
            $str .= ' class="selected"';
        }
        $str .= "><a href='?bo_table={$bo_table}&amp;sca={$categories[$i]}'>{$categories[$i]}</a></li>\n";
    }

    return $str;
}

// 그룹 선택 셀렉트
function get_group_select($name, $selected = '', $event = '')
{
    global $g5, $is_admin, $member;

    $sql = "select gr_id, gr_subject from {$g5['group_table']} a";
    if ($is_admin == 'group') {
        $sql .= " left join {$g5['member_table']} b on (b.mb_id = a.gr_admin)
                  where b.mb_id = '{$member['mb_id']}'";
    }
    $sql .= " order by a.gr_id";

    $result = sql_query($sql);

    $str = '<select id="'.$name.'" name="'.$name.'" '.$event.'>'."\n";
    for ($i = 0; $row = sql_fetch_array($result); $i++) {
        if ($i == 0) {
            $str .= '<option value="">선택</option>';
        }
        $str .= option_selected($row['gr_id'], $selected, $row['gr_subject']);
    }
    $str .= '</select>';

    return $str;
}

function option_selected($value, $selected, $text = '')
{
    if ($text === '') {
        $text = $value;
    }

    if ((string)$value === (string)$selected) {
        return '<option value="'.$value.'" selected="selected">'.$text."</option>\n";
    }

    return '<option value="'.$value.'">'.$text."</option>\n";
}

// 포인트 부여
function insert_point($mb_id, $point, $content = '', $rel_table = '', $rel_id = '', $rel_action = '', $expire = 0)
{
    global $config, $g5;

    if (empty($config['cf_use_point'])) return 0;
    if ($point == 0) return 0;
    if ($mb_id === '') return 0;

    $mb = sql_fetch("select mb_id from {$g5['member_table']} where mb_id = '{$mb_id}'");
    if (empty($mb['mb_id'])) return 0;

    $mb_point = get_point_sum($mb_id);

    if ($rel_table || $rel_id || $rel_action) {
        $sql = "select count(*) as cnt
                  from {$g5['point_table']}
                 where mb_id = '{$mb_id}'
                   and po_rel_table = '{$rel_table}'
                   and po_rel_id = '{$rel_id}'
                   and po_rel_action = '{$rel_action}'";
        $row = sql_fetch($sql);
        if (!empty($row['cnt'])) {
            return -1;
        }
    }

    $po_mb_point = $mb_point + $point;

    $sql = "insert into {$g5['point_table']}
               set mb_id = '{$mb_id}',
                   po_datetime = '".G5_TIME_YMDHIS."',
                   po_content = '".addslashes($content)."',
                   po_point = '{$point}',
                   po_use_point = '0',
                   po_mb_point = '{$po_mb_point}',
                   po_rel_table = '{$rel_table}',
                   po_rel_id = '{$rel_id}',
                   po_rel_action = '{$rel_action}'";
    sql_query($sql);

    if ($point < 0) {
        insert_use_point($mb_id, $point);
    }

    sql_query("update {$g5['member_table']} set mb_point = '{$po_mb_point}' where mb_id = '{$mb_id}'");

    if (function_exists('mdl_do_action')) {
        mdl_do_action('point_insert_after', $mb_id, $point, $content, $rel_table, $rel_id, $rel_action);
    }

    return 1;
}

// 사용포인트 입력
function insert_use_point($mb_id, $point, $po_id = '')
{
    global $g5;

    $sql_order = "order by po_id asc";
    $point1 = abs($point);

    $sql = "select po_id, po_point, po_use_point
              from {$g5['point_table']}
             where mb_id = '{$mb_id}'
               and po_id <> '{$po_id}'
               and po_expired = '0'
               and po_point > po_use_point
             {$sql_order}";
    $result = sql_query($sql);

    for ($i = 0; $row = sql_fetch_array($result); $i++) {
        $remain = $row['po_point'] - $row['po_use_point'];

        if ($remain > $point1) {
            sql_query("update {$g5['point_table']}
                          set po_use_point = po_use_point + '{$point1}'
                        where po_id = '{$row['po_id']}'");
            break;
        } else {
            sql_query("update {$g5['point_table']}
                          set po_use_point = po_use_point + '{$remain}',
                              po_expired = '100'
                        where po_id = '{$row['po_id']}'");
            $point1 -= $remain;
            if ($point1 <= 0) break;
        }
    }
}

// 사용포인트 삭제
function delete_use_point($mb_id, $point)
{
    global $g5;

    $sql_order = "order by po_id desc";
    $point1 = abs($point);

    $sql = "select po_id, po_use_point
              from {$g5['point_table']}
             where mb_id = '{$mb_id}'
               and po_expired <> '1'
               and po_use_point > 0
             {$sql_order}";
    $result = sql_query($sql);

    for ($i = 0; $row = sql_fetch_array($result); $i++) {
        if ($row['po_use_point'] > $point1) {
            sql_query("update {$g5['point_table']}
                          set po_use_point = po_use_point - '{$point1}'
                        where po_id = '{$row['po_id']}'");
            break;
        } else {
            sql_query("update {$g5['point_table']}
                          set po_use_point = '0'
                        where po_id = '{$row['po_id']}'");
            $point1 -= $row['po_use_point'];
            if ($point1 <= 0) break;
        }
    }
}

// 소멸포인트 삭제
function delete_expire_point($mb_id, $point)
{
    global $g5;

    $point1 = abs($point);

    $sql = "select po_id, po_use_point
              from {$g5['point_table']}
             where mb_id = '{$mb_id}'
               and po_point >= 0
               and po_use_point > 0
             order by po_id desc";
    $result = sql_query($sql);

    for ($i = 0; $row = sql_fetch_array($result); $i++) {
        if ($row['po_use_point'] > $point1) {
            sql_query("update {$g5['point_table']}
                          set po_use_point = po_use_point - '{$point1}'
                        where po_id = '{$row['po_id']}'");
            break;
        } else {
            sql_query("update {$g5['point_table']}
                          set po_use_point = '0'
                        where po_id = '{$row['po_id']}'");
            $point1 -= $row['po_use_point'];
            if ($point1 <= 0) break;
        }
    }
}

// 포인트 합계
function get_point_sum($mb_id)
{
    global $g5;

    $row = sql_fetch("select sum(po_point) as sum_po_point from {$g5['point_table']} where mb_id = '{$mb_id}'");
    return isset($row['sum_po_point']) ? (int)$row['sum_po_point'] : 0;
}

// 포인트 삭제
function delete_point($mb_id, $rel_table, $rel_id, $rel_action)
{
    global $g5;

    if (!($rel_table || $rel_id || $rel_action)) {
        return false;
    }

    $sql = "select *
              from {$g5['point_table']}
             where mb_id = '{$mb_id}'
               and po_rel_table = '{$rel_table}'
               and po_rel_id = '{$rel_id}'
               and po_rel_action = '{$rel_action}'";
    $row = sql_fetch($sql);

    if (!$row) {
        return false;
    }

    if ($row['po_point'] < 0) {
        delete_use_point($row['mb_id'], abs($row['po_point']));
    } else {
        if ($row['po_use_point'] > 0) {
            insert_use_point($row['mb_id'], $row['po_use_point'], $row['po_id']);
        }
    }

    sql_query("delete from {$g5['point_table']}
                where mb_id = '{$mb_id}'
                  and po_rel_table = '{$rel_table}'
                  and po_rel_id = '{$rel_id}'
                  and po_rel_action = '{$rel_action}'", false);

    sql_query("update {$g5['point_table']}
                  set po_mb_point = po_mb_point - '{$row['po_point']}'
                where mb_id = '{$mb_id}'
                  and po_id > '{$row['po_id']}'");

    $sum_point = get_point_sum($mb_id);

    sql_query("update {$g5['member_table']} set mb_point = '{$sum_point}' where mb_id = '{$mb_id}'");

    if (function_exists('mdl_do_action')) {
        mdl_do_action('point_delete_after', $mb_id, $rel_table, $rel_id, $rel_action, $row);
    }

    return true;
}

// 회원 레이어
function get_sideview($mb_id, $name = '')
{
    global $bo_table, $sca, $is_admin;

    $name = get_text($name, 0, true);

    if ($mb_id) {
        $link = G5_BBS_URL.'/profile.php?mb_id='.$mb_id;
        $tmp_name = '<a href="'.$link.'" class="sv_member" title="'.$name.' 자기소개" target="_blank" onclick="return false;"> '.$name.'</a>';
        $title_mb_id = '['.$mb_id.']';
    } else {
        if (!$bo_table) {
            return $name;
        }
        $href = G5_BBS_URL.'/board.php?bo_table='.$bo_table.'&amp;sca='.$sca.'&amp;sfl=wr_name,1&amp;stx='.$name;
        $tmp_name = '<a href="'.$href.'" title="'.$name.' 이름으로 검색" class="sv_guest" onclick="return false;">'.$name.'</a>';
        $title_mb_id = '[비회원]';
    }

    $str = "<span class=\"sv_wrap\">\n".$tmp_name."\n";

    $str2 = "<span class=\"sv\">\n";
    if ($mb_id) {
        $str2 .= '<a href="'.G5_BBS_URL.'/memo_form.php?me_recv_mb_id='.$mb_id.'" onclick="win_memo(this.href); return false;">쪽지보내기</a>'."\n";
    }
    if ($bo_table) {
        if ($mb_id) {
            $str2 .= '<a href="'.G5_BBS_URL.'/board.php?bo_table='.$bo_table.'&amp;sca='.$sca.'&amp;sfl=mb_id,1&amp;stx='.$mb_id.'">아이디로 검색</a>'."\n";
        } else {
            $str2 .= '<a href="'.G5_BBS_URL.'/board.php?bo_table='.$bo_table.'&amp;sca='.$sca.'&amp;sfl=wr_name,1&amp;stx='.$name.'">이름으로 검색</a>'."\n";
        }
    }
    if ($mb_id) {
        $str2 .= '<a href="'.G5_BBS_URL.'/new.php?mb_id='.$mb_id.'">전체게시물</a>'."\n";
    }
    if ($is_admin && $mb_id) {
        $str2 .= '<a href="'.G5_ADMIN_URL.'/member_form.php?w=u&amp;mb_id='.$mb_id.'" target="_blank">회원정보변경</a>'."\n";
        $str2 .= '<a href="'.G5_ADMIN_URL.'/point_list.php?sfl=mb_id&amp;stx='.$mb_id.'" target="_blank">포인트내역</a>'."\n";
    }
    $str2 .= "</span>\n";

    $str .= $str2;
    $str .= "\n<noscript class=\"sv_nojs\">".$str2."</noscript>";
    $str .= "</span>";

    if (function_exists('mdl_apply_filters')) {
        $str = mdl_apply_filters('sideview_html', $str, $mb_id);
    }

    return $str;
}

// 파일 링크 (이미지 전용 처리)
function view_file_link($file, $width, $height, $content = '')
{
    global $config, $board, $g5;
    static $ids = 0;

    if (!$file) {
        return '';
    }

    $ids++;

    if (!empty($board['bo_image_width']) && $width > $board['bo_image_width']) {
        $rate = $board['bo_image_width'] / $width;
        $width = $board['bo_image_width'];
        $height = (int)($height * $rate);
    }

    $attr = $width ? ' width="'.$width.'" height="'.$height.'" ' : '';

    if (!empty($config['cf_image_extension']) && preg_match("/\.({$config['cf_image_extension']})$/i", $file)) {
        $rel = 'file/'.$board['bo_table'].'/'.urlencode($file);
        $src = function_exists('cdn_data_url') ? cdn_data_url($rel) : G5_DATA_URL.'/'.$rel;
        $view = G5_BBS_URL.'/view_image.php?bo_table='.$board['bo_table'].'&amp;fn='.urlencode($file);

        $html = '<a href="'.$view.'" target="_blank" class="view_image">'
              . '<img src="'.$src.'" alt="'.$content.'"'.$attr.'>'
              . '</a>';

        if (function_exists('mdl_apply_filters')) {
            $html = mdl_apply_filters('view_file_link', $html, $file, $board['bo_table']);
        }

        return $html;
    }

    return '';
}

// {img:n} 형식 교체
function view_image($view, $number, $attribute)
{
    if (!empty($view['file'][$number]['view'])) {
        return preg_replace('/>$/', ' '.$attribute.'>', $view['file'][$number]['view']);
    }

    return '';
}

// 멀티바이트 안전 자르기
function cut_str($str, $len, $suffix = '…')
{
    if (!is_string($str)) {
        $str = (string)$str;
    }

    $len = (int)$len;
    if ($len <= 0) {
        return '';
    }

    // UTF-8 기준 한 글자씩 분리
    $arr_str = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
    if ($arr_str === false) {
        // 인코딩 깨진 경우 fallback
        return mb_strlen($str, 'UTF-8') > $len
            ? mb_substr($str, 0, $len, 'UTF-8').$suffix
            : $str;
    }

    $str_len = count($arr_str);

    if ($str_len >= $len) {
        $slice_str = array_slice($arr_str, 0, $len);
        $cut = implode('', $slice_str);
        return $cut.($str_len > $len ? $suffix : '');
    }

    return implode('', $arr_str);
}

// TEXT 형식으로 변환
// $html == 0 : 순수 텍스트 출력용 (HTML 엔티티 이스케이프 + html_symbol 처리)
// $html != 0 : 개행을 <br/> 로 치환
// $restore == true : 이전에 get_text로 이스케이프된 것을 복원
function get_text($str, $html = 0, $restore = false)
{
    if (!is_string($str)) {
        $str = (string)$str;
    }

    $source = array('<', '>', '"', '\'',);
    $target = array('&lt;', '&gt;', '&#034;', '&#039;',);

    if ($restore) {
        // 저장된 이스케이프를 원본으로 복구
        $str = str_replace($target, $source, $str);
    }

    // TEXT 출력 모드에서는 기존 HTML 엔티티를 안전하게 처리
    if ($html == 0) {
        $str = html_symbol($str);
    }

    if ($html) {
        // 개행을 <br/> 로 치환
        $source[] = "\n";
        $target[] = "<br/>";
    }

    return str_replace($source, $target, $str);
}

// HTML SYMBOL 변환
// &nbsp; &amp; 등 기존 엔티티를 이중 해석되지 않도록 처리
function html_symbol($str)
{
    if (!is_string($str)) {
        $str = (string)$str;
    }

    return preg_replace(
        "/\&([a-z0-9]{1,20}|\#[0-9]{1,3});/i",
        "&#038;\\1;",
        $str
    );
}

/*************************************************************************
**
**  SQL 관련 함수 모음
**
*************************************************************************/

// DB 연결 (PHP 8: mysqli 전용)
function sql_connect($host, $user, $pass, $db = G5_MYSQL_DB)
{
    global $g5;

    // PHP 8.1+ 는 mysqli 기본 에러 모드가 예외(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT)로
    // 바뀌어서, 쿼리 실패 시 아래 '@' 억제나 sql_query() 의 false 체크가 무의미해지고
    // 처리되지 않은 mysqli_sql_exception 이 그대로 치명적 오류(HTTP 500)로 이어진다.
    // 과거(PHP 5/7)처럼 실패 시 false 를 반환하도록 되돌려서 die() 기반 에러 처리가 동작하게 한다.
    mysqli_report(MYSQLI_REPORT_OFF);

    $link = @mysqli_connect($host, $user, $pass, $db);

    if (!$link) {
        die('Connect Error ('.mysqli_connect_errno().'): '.mysqli_connect_error());
    }

    // 전역 기본 연결 저장 (없을 경우만)
    if (!isset($g5['connect_db']) || !$g5['connect_db']) {
        $g5['connect_db'] = $link;
    }

    return $link;
}

// DB 선택
function sql_select_db($db, $connect)
{
    return @mysqli_select_db($connect, $db);
}

// charset 설정
function sql_set_charset($charset, $link = null)
{
    global $g5;

    if (!$link) {
        $link = isset($g5['connect_db']) ? $g5['connect_db'] : null;
    }

    if ($link) {
        @mysqli_set_charset($link, $charset);
    }
}

// 공용 쿼리 실행
function sql_query($sql, $error = G5_DISPLAY_SQL_ERROR, $link = null)
{
    global $g5;

    if (!$link) {
        $link = isset($g5['connect_db']) ? $g5['connect_db'] : null;
    }
    if (!$link) {
        die('DB connection not initialized.');
    }

    // 최소 방어
    $sql = trim($sql);
    $sql = preg_replace("#^select.*from.*[\s\(]+union[\s\)]+.*#i", "select 1", $sql);
    $sql = preg_replace("#^select.*from.*where.*`?information_schema`?.*#i", "select 1", $sql);

    if ($error) {
        $result = @mysqli_query($link, $sql);
        if ($result === false) {
            die(
                "<p>{$sql}<p>".
                mysqli_errno($link).' : '.mysqli_error($link).
                "<p>error file : ".$_SERVER['SCRIPT_NAME']
            );
        }
    } else {
        $result = @mysqli_query($link, $sql);
    }

    return $result;
}

// 한 행 fetch
function sql_fetch($sql, $error = G5_DISPLAY_SQL_ERROR, $link = null)
{
    $result = sql_query($sql, $error, $link);
    return sql_fetch_array($result);
}

// 연관배열 fetch
function sql_fetch_array($result)
{
    if (!$result) {
        return null;
    }
    return @mysqli_fetch_assoc($result);
}

// 결과 free
function sql_free_result($result)
{
    if ($result) {
        @mysqli_free_result($result);
    }
}

// PASSWORD() 래퍼 (서버 함수 사용)
function sql_password($value)
{
    $row = sql_fetch("select password('".addslashes($value)."') as pass");
    return isset($row['pass']) ? $row['pass'] : '';
}

// insert_id
function sql_insert_id($link = null)
{
    global $g5;

    if (!$link) {
        $link = isset($g5['connect_db']) ? $g5['connect_db'] : null;
    }
    if (!$link) {
        return 0;
    }

    return mysqli_insert_id($link);
}

// num_rows
function sql_num_rows($result)
{
    if (!$result) {
        return 0;
    }
    return mysqli_num_rows($result);
}

// affected_rows — 직전 sql_query 가 영향을 준 행 수
function sql_affected_rows($link = null)
{
    global $g5;
    if (!$link) {
        $link = isset($g5['connect_db']) ? $g5['connect_db'] : null;
    }
    if (!$link) {
        return 0;
    }
    return mysqli_affected_rows($link);
}

// 테이블 컬럼 이름 목록
function sql_field_names($table, $link = null)
{
    global $g5;

    if (!$link) {
        $link = isset($g5['connect_db']) ? $g5['connect_db'] : null;
    }
    if (!$link) {
        die('DB connection not initialized.');
    }

    $columns = array();

    $table_esc = '`'.str_replace('`', '``', $table).'`';
    $sql = "select * from {$table_esc} limit 1";

    $result = sql_query($sql, G5_DISPLAY_SQL_ERROR, $link);

    if ($result) {
        while ($field = mysqli_fetch_field($result)) {
            $columns[] = $field->name;
        }
        sql_free_result($result);
    }

    return $columns;
}

// 마지막 에러 정보
function sql_error_info($link = null)
{
    global $g5;

    if (!$link) {
        $link = isset($g5['connect_db']) ? $g5['connect_db'] : null;
    }
    if (!$link) {
        return '';
    }

    return mysqli_errno($link).' : '.mysqli_error($link);
}

// 테이블 DDL 생성 (간이)
function get_table_define($table, $crlf = "\n")
{
    $schema_create = '';
    $index = array();

    $table_esc = '`'.str_replace('`', '``', $table).'`';

    $schema_create .= 'CREATE TABLE '.$table_esc.' ('.$crlf;

    // 필드 정의
    $result = sql_query('SHOW FIELDS FROM '.$table_esc);
    while ($row = sql_fetch_array($result)) {
        $schema_create .= '    '.$row['Field'].' '.$row['Type'];

        if (isset($row['Default']) && $row['Default'] !== '') {
            $schema_create .= " DEFAULT '".$row['Default']."'";
        }
        if ($row['Null'] !== 'YES') {
            $schema_create .= ' NOT NULL';
        }
        if (!empty($row['Extra'])) {
            $schema_create .= ' '.$row['Extra'];
        }
        $schema_create .= ','.$crlf;
    }
    sql_free_result($result);

    $schema_create = preg_replace('/,'.$crlf.'$/', '', $schema_create);

    // 인덱스 정의
    $result = sql_query('SHOW KEYS FROM '.$table_esc);
    while ($row = sql_fetch_array($result)) {
        $kname    = $row['Key_name'];
        $comment  = isset($row['Comment']) ? $row['Comment'] : '';
        $sub_part = isset($row['Sub_part']) ? $row['Sub_part'] : '';

        if ($kname != 'PRIMARY' && $row['Non_unique'] == 0) {
            $kname = 'UNIQUE|'.$kname;
        }
        if ($comment === 'FULLTEXT') {
            $kname = 'FULLTEXT|'.$kname;
        }

        if (!isset($index[$kname])) {
            $index[$kname] = array();
        }

        if ($sub_part > 1) {
            $index[$kname][] = $row['Column_name'].'('.$sub_part.')';
        } else {
            $index[$kname][] = $row['Column_name'];
        }
    }
    sql_free_result($result);

    foreach ($index as $x => $columns) {
        $schema_create .= ','.$crlf;
        if ($x === 'PRIMARY') {
            $schema_create .= '    PRIMARY KEY (';
        } else if (substr($x, 0, 6) === 'UNIQUE') {
            $schema_create .= '    UNIQUE '.substr($x, 7).' (';
        } else if (substr($x, 0, 8) === 'FULLTEXT') {
            $schema_create .= '    FULLTEXT '.substr($x, 9).' (';
        } else {
            $schema_create .= '    KEY '.$x.' (';
        }
        $schema_create .= implode(', ', $columns).')';
    }

    $schema_create .= $crlf.') ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    return $schema_create;
}

// 리퍼러 체크 (비활성)
function referer_check($url = '')
{
    // 의도적으로 비워둠
}

// 한글 요일
function get_yoil($date, $full = 0)
{
    $arr_yoil = array('일', '월', '화', '수', '목', '금', '토');

    $ts = strtotime($date);
    if ($ts === false) {
        return '';
    }

    $yoil = (int)date('w', $ts);
    $str = $arr_yoil[$yoil];

    if ($full) {
        $str .= '요일';
    }

    return $str;
}


// DEMO 파일 존재 시 데모 모드로 처리
function check_demo()
{
    global $is_admin;

    if (!$is_admin && file_exists(G5_PATH.'/DEMO')) {
        alert('데모 화면에서는 하실(보실) 수 없는 작업입니다.');
    }
}

// 문자열이 한글, 영문, 숫자, 특수문자로 구성되어 있는지 검사
function check_string($str, $options)
{
    $s = '';
    $len = strlen($str);

    for ($i = 0; $i < $len; $i++) {
        $c = $str[$i];
        $oc = ord($c);

        // 한글 (구 코드; UTF-8 완전 대응 아님, 기존 로직 유지)
        if ($oc >= 0xA0 && $oc <= 0xFF) {
            if ($options & G5_HANGUL) {
                if (isset($str[$i + 1], $str[$i + 2])) {
                    $s .= $c.$str[$i + 1].$str[$i + 2];
                }
            }
            $i += 2;
        }
        // 숫자
        else if ($oc >= 0x30 && $oc <= 0x39) {
            if ($options & G5_NUMERIC) {
                $s .= $c;
            }
        }
        // 영대문자
        else if ($oc >= 0x41 && $oc <= 0x5A) {
            if (($options & G5_ALPHABETIC) || ($options & G5_ALPHAUPPER)) {
                $s .= $c;
            }
        }
        // 영소문자
        else if ($oc >= 0x61 && $oc <= 0x7A) {
            if (($options & G5_ALPHABETIC) || ($options & G5_ALPHALOWER)) {
                $s .= $c;
            }
        }
        // 공백
        else if ($oc === 0x20) {
            if ($options & G5_SPACE) {
                $s .= $c;
            }
        }
        // 특수문자
        else {
            if ($options & G5_SPECIAL) {
                $s .= $c;
            }
        }
    }

    return ($str === $s);
}

// 한글 반쪽 글자 방지용(현재 로직은 카운트만, 기존 호환 유지)
function cut_hangul_last($hangul)
{
    return $hangul;
}

// 인덱스 사용 여부 확인용 디버그
function explain($sql)
{
    $trim = trim($sql);
    if (preg_match("/^select/i", $trim)) {
        $q = "EXPLAIN {$trim}";
        echo $q;
        $row = sql_fetch($q);
        if (empty($row['key'])) {
            $row['key'] = 'NULL';
        }
        echo ' <font color="blue">(type='.$row['type'].' , key='.$row['key'].')</font>';
    }
}

// 악성 태그 변환
function bad_tag_convert($code)
{
    global $view, $member, $is_admin;

    // 관리자 계정이면서 작성자와 다른 경우 embed/object 차단
    if ($is_admin && isset($member['mb_id'], $view['mb_id']) && $member['mb_id'] !== $view['mb_id']) {
        $code = preg_replace_callback(
            "#(\<(embed|object)[^\>]*)\>?(\<\/(embed|object)\>)?#i",
            function () {
                return '<div class="embedx">보안문제로 인하여 관리자 아이디로는 embed 또는 object 태그를 볼 수 없습니다. 확인하시려면 관리권한이 없는 다른 아이디로 접속하세요.</div>';
            },
            $code
        );
    }

    // script, iframe, form 태그 치환
    return preg_replace(
        "/\<([\/]?)(script|iframe|form)([^\>]*)\>?/i",
        "&lt;$1$2$3&gt;",
        $code
    );
}

// 토큰 생성
function _token()
{
    return md5(uniqid(mt_rand(), true));
}

// 토큰 발급 및 세션 저장
function get_token()
{
    $token = md5(uniqid(mt_rand(), true));
    set_session('ss_token', $token);
    return $token;
}

// 토큰 검증 (현재 구현: 토큰 초기화만, 기존 코드 호환)
function check_token()
{
    set_session('ss_token', '');
    return true;
}

// 문자열이 UTF-8 인코딩인지 검사
function is_utf8($str)
{
    $len = strlen($str);

    for ($i = 0; $i < $len; $i++) {
        $c = ord($str[$i]);

        if ($c > 128) {
            if ($c > 247) return false;
            else if ($c > 239) $bytes = 4;
            else if ($c > 223) $bytes = 3;
            else if ($c > 191) $bytes = 2;
            else return false;

            if (($i + $bytes) > $len) return false;

            while ($bytes > 1) {
                $i++;
                $b = ord($str[$i]);
                if ($b < 128 || $b > 191) return false;
                $bytes--;
            }
        }
    }

    return true;
}

// UTF-8 문자열 자르기 (기존 호환 로직)
function utf8_strcut($str, $size, $suffix = '...')
{
    $substr = substr($str, 0, $size * 2);
    $multi_size = preg_match_all('/[\x80-\xff]/', $substr, $multi_chars);

    if ($multi_size > 0) {
        $size = $size + (int)($multi_size / 3) - 1;
    }

    if (strlen($str) > $size) {
        $str = substr($str, 0, $size);
        $str = preg_replace('/(([\x80-\xff]{3})*?)([\x80-\xff]{0,2})$/', '$1', $str);
        $str .= $suffix;
    }

    return $str;
}

// 문자셋 변환
function convert_charset($from_charset, $to_charset, $str)
{
    if (function_exists('iconv')) {
        return iconv($from_charset, $to_charset, $str);
    }

    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($str, $to_charset, $from_charset);
    }

    die("Not found 'iconv' or 'mbstring' library in server.");
}

// mysqli_real_escape_string alias
function sql_real_escape_string($str, $link = null)
{
    global $g5;

    if (!$link) {
        $link = isset($g5['connect_db']) ? $g5['connect_db'] : null;
    }
    if (!$link) {
        return addslashes($str);
    }

    return mysqli_real_escape_string($link, $str);
}

function escape_trim($field)
{
    if (!defined('G5_ESCAPE_FUNCTION')) {
        return trim($field);
    }

    $str = call_user_func(G5_ESCAPE_FUNCTION, $field);
    return $str;
}

// checkbox checked 여부
function is_checked($field)
{
    return !empty($_POST[$field]);
}

// IP를 정수로
function abs_ip2long($ip = '')
{
    if ($ip === '') {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    return abs(ip2long($ip));
}

function get_selected($field, $value)
{
    return ($field == $value) ? ' selected="selected"' : '';
}

function get_checked($field, $value)
{
    return ($field == $value) ? ' checked="checked"' : '';
}

// 모바일 접속 여부
function is_mobile()
{
    if (!defined('G5_MOBILE_AGENT')) {
        return 0;
    }

    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    if ($ua === '') {
        return 0;
    }

    return preg_match('/'.G5_MOBILE_AGENT.'/i', $ua);
}

// 유일 키 생성
function get_uniqid()
{
    global $g5;

    $tbl = $g5['uniqid_table'];

    sql_query("LOCK TABLE {$tbl} WRITE");

    while (true) {
        $key = date('ymdHis').str_pad((int)(microtime(true) * 100) % 100, 2, '0', STR_PAD_LEFT);

        $ip = isset($_SERVER['REMOTE_ADDR']) ? sql_real_escape_string($_SERVER['REMOTE_ADDR']) : '';
        $result = sql_query("INSERT INTO {$tbl} SET uq_id = '{$key}', uq_ip = '{$ip}'", false);

        if ($result) {
            break;
        }

        usleep(10000);
    }

    sql_query("UNLOCK TABLES");

    return $key;
}

// CHARSET 변경 : euc-kr -> utf-8
function iconv_utf8($str)
{
    return iconv('euc-kr', 'utf-8', $str);
}

// CHARSET 변경 : utf-8 -> euc-kr
function iconv_euckr($str)
{
    return iconv('utf-8', 'euc-kr', $str);
}

// PC/모바일 전용 게시판 체크
function check_device($device)
{
    global $is_admin;

    if ($is_admin) {
        return;
    }

    if ($device === 'pc' && defined('G5_IS_MOBILE') && G5_IS_MOBILE) {
        alert('PC 전용 게시판입니다.', G5_URL);
    } else if ($device === 'mobile' && (!defined('G5_IS_MOBILE') || !G5_IS_MOBILE)) {
        alert('모바일 전용 게시판입니다.', G5_URL);
    }
}

// 최신글 캐시 삭제
function delete_cache_latest($bo_table)
{
    $files = glob(G5_DATA_PATH.'/cache/latest-'.$bo_table.'-*');
    if (is_array($files)) {
        foreach ($files as $filename) {
            @unlink($filename);
        }
    }
}

// 게시판 썸네일 삭제
function delete_board_thumbnail($bo_table, $file)
{
    if (!$bo_table || !$file) return;

    $fn = preg_replace("/\.[^\.]+$/i", "", basename($file));
    $files = glob(G5_DATA_PATH.'/file/'.$bo_table.'/thumb-'.$fn.'*');
    if (is_array($files)) {
        foreach ($files as $filename) {
            @unlink($filename);
        }
    }

    if (function_exists('mdl_do_action')) {
        mdl_do_action('board_thumbnail_delete_after', $bo_table, $file, is_array($files) ? $files : array());
    }
}

// 에디터 이미지 추출
function get_editor_image($contents, $view = true)
{
    if (!$contents) return false;

    if ($view) {
        $pattern = "/<img([^>]*)>/iS";
    } else {
        $pattern = "/<img[^>]*src=[\'\"]?([^>\'\"]+[^>\'\"]+)[\'\"]?[^>]*>/i";
    }

    preg_match_all($pattern, $contents, $matchs);

    return $matchs;
}

// 에디터 썸네일 삭제
function delete_editor_thumbnail($contents)
{
    if (!$contents) return;

    $matchs = get_editor_image($contents);
    if (!$matchs || !isset($matchs[1])) return;

    $cnt = count($matchs[1]);

    for ($i = 0; $i < $cnt; $i++) {
        $imgurl = parse_url($matchs[1][$i]);
        if (!isset($imgurl['path'])) continue;

        $srcfile = $_SERVER['DOCUMENT_ROOT'].$imgurl['path'];

        $filename = preg_replace("/\.[^\.]+$/i", "", basename($srcfile));
        $filepath = dirname($srcfile);
        $files = glob($filepath.'/thumb-'.$filename.'*');

        if (is_array($files)) {
            foreach ($files as $filename) {
                @unlink($filename);
            }
        }
    }

    if (function_exists('mdl_do_action')) {
        mdl_do_action('editor_thumbnail_delete_after', isset($matchs) ? $matchs : array(), $contents);
    }
}

// 1:1문의 썸네일 삭제
function delete_qa_thumbnail($file)
{
    if (!$file) return;

    $fn = preg_replace("/\.[^\.]+$/i", "", basename($file));
    $files = glob(G5_DATA_PATH.'/qa/thumb-'.$fn.'*');
    if (is_array($files)) {
        foreach ($files as $filename) {
            @unlink($filename);
        }
    }
}

// 스킨 CSS 로딩
function get_skin_stylesheet($skin_path, $dir = '')
{
    if (!$skin_path) return '';

    $files = array();
    $str = '';

    if ($dir) {
        $skin_path .= '/'.$dir;
    }

    $skin_url = G5_URL.str_replace("\\", "/", str_replace(G5_PATH, "", $skin_path));

    if (is_dir($skin_path) && ($dh = opendir($skin_path))) {
        while (($file = readdir($dh)) !== false) {
            if ($file === '.' || $file === '..') continue;
            if (is_dir($skin_path.'/'.$file)) continue;
            if (preg_match("/\.css$/i", $file)) {
                $files[] = $file;
            }
        }
        closedir($dh);
    }

    if (!empty($files)) {
        sort($files);
        $ver = date('md');

        foreach ($files as $file) {
            $str .= '<link rel="stylesheet" href="'.$skin_url.'/'.$file.'?='.$ver.'">'."\n";
        }
    }

    return $str;
}

// 스킨 JS 로딩
function get_skin_javascript($skin_path, $dir = '')
{
    if (!$skin_path) return '';

    $files = array();
    $str = '';

    if ($dir) {
        $skin_path .= '/'.$dir;
    }

    $skin_url = G5_URL.str_replace("\\", "/", str_replace(G5_PATH, "", $skin_path));

    if (is_dir($skin_path) && ($dh = opendir($skin_path))) {
        while (($file = readdir($dh)) !== false) {
            if ($file === '.' || $file === '..') continue;
            if (is_dir($skin_path.'/'.$file)) continue;
            if (preg_match("/\.js$/i", $file)) {
                $files[] = $file;
            }
        }
        closedir($dh);
    }

    if (!empty($files)) {
        sort($files);
        foreach ($files as $file) {
            $str .= '<script src="'.$skin_url.'/'.$file.'"></script>'."\n";
        }
    }

    return $str;
}

// file_put_contents 폴리필 (PHP 5 미만용, PHP 8에서는 내장함수 사용)
if (!function_exists('file_put_contents')) {
    function file_put_contents($filename, $data)
    {
        $f = @fopen($filename, 'w');
        if (!$f) return false;
        $bytes = fwrite($f, $data);
        fclose($f);
        return $bytes;
    }
}

// HTML 마지막 처리
function html_end()
{
    global $html_process;

    if (!isset($html_process) || !($html_process instanceof html_process)) {
        return '';
    }

    return $html_process->run();
}

function add_stylesheet($stylesheet, $order = 0)
{
    global $html_process;

    if (!isset($html_process) || !($html_process instanceof html_process)) {
        return;
    }

    if (trim($stylesheet) !== '') {
        $html_process->merge_stylesheet($stylesheet, $order);
    }
}

function add_javascript($javascript, $order = 0)
{
    global $html_process;

    if (!isset($html_process) || !($html_process instanceof html_process)) {
        return;
    }

    if (trim($javascript) !== '') {
        $html_process->merge_javascript($javascript, $order);
    }
}

class html_process
{
    protected $css = array();
    protected $js = array();

    public function merge_stylesheet($stylesheet, $order)
    {
        if (!is_string($stylesheet) || $stylesheet === '') return;

        foreach ($this->css as $link) {
            if (isset($link[1]) && $link[1] === $stylesheet) {
                return;
            }
        }

        $this->css[] = array((int)$order, $stylesheet);
    }

    public function merge_javascript($javascript, $order)
    {
        if (!is_string($javascript) || $javascript === '') return;

        foreach ($this->js as $script) {
            if (isset($script[1]) && $script[1] === $javascript) {
                return;
            }
        }

        $this->js[] = array((int)$order, $javascript);
    }

    public function run()
    {
        global $config, $g5, $member;

        if (!isset($g5) || !is_array($g5)) $g5 = array();
        if (!isset($member) || !is_array($member)) $member = array();
        if (!isset($config) || !is_array($config)) $config = array();
        if (!isset($g5['login_table'])) $g5['login_table'] = 'login';

        $mb_id = isset($member['mb_id']) ? $member['mb_id'] : '';
        $lo_location = isset($g5['lo_location']) ? $g5['lo_location'] : '';
        $lo_url = isset($g5['lo_url']) ? $g5['lo_url'] : '';
        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $login_minutes = 10;
        if ($login_minutes <= 0) $login_minutes = 10;

        // 현재 접속자 처리
        if ($remote_ip !== '') {
            $ip_esc = sql_real_escape_string($remote_ip);
            $tmp_sql = "SELECT COUNT(*) AS cnt FROM {$g5['login_table']} WHERE lo_ip = '{$ip_esc}'";
            $tmp_row = sql_fetch($tmp_sql);

            if (!empty($tmp_row['cnt'])) {
                $sql = "
                    UPDATE {$g5['login_table']}
                       SET mb_id = '".sql_real_escape_string($mb_id)."',
                           lo_datetime = '".G5_TIME_YMDHIS."',
                           lo_location = '".sql_real_escape_string($lo_location)."',
                           lo_url = '".sql_real_escape_string($lo_url)."'
                     WHERE lo_ip = '{$ip_esc}'
                ";
                sql_query($sql, false);
            } else {
                $sql = "
                    INSERT INTO {$g5['login_table']}
                        (lo_ip, mb_id, lo_datetime, lo_location, lo_url)
                    VALUES (
                        '{$ip_esc}',
                        '".sql_real_escape_string($mb_id)."',
                        '".G5_TIME_YMDHIS."',
                        '".sql_real_escape_string($lo_location)."',
                        '".sql_real_escape_string($lo_url)."'
                    )
                ";
                sql_query($sql, false);

                $expire = date('Y-m-d H:i:s', G5_SERVER_TIME - (60 * $login_minutes));
                sql_query("DELETE FROM {$g5['login_table']} WHERE lo_datetime < '{$expire}'", false);
            }
        }

        // 출력 버퍼
        $buffer = '';
        if (ob_get_level() > 0) {
            $tmp = ob_get_contents();
            if ($tmp !== false) {
                $buffer = $tmp;
            }
            ob_end_clean();
        }

        // CSS 병합
        $stylesheet = '';
        if (!empty($this->css)) {
            $links = $this->css;
            $order = array();
            $index = array();

            foreach ($links as $k => $row) {
                $order[$k] = isset($row[0]) ? (int)$row[0] : 0;
                $index[$k] = $k;
            }

            array_multisort($order, SORT_ASC, $index, SORT_ASC, $links);

            foreach ($links as $link) {
                if (!isset($link[1])) continue;
                $href = trim($link[1]);
                if ($href === '') continue;
                $stylesheet .= PHP_EOL.$href;
            }
        }

        // JS 병합
        $javascript = '';
        if (!empty($this->js)) {
            $scripts = $this->js;
            $order = array();
            $index = array();

            foreach ($scripts as $k => $row) {
                $order[$k] = isset($row[0]) ? (int)$row[0] : 0;
                $index[$k] = $k;
            }

            array_multisort($order, SORT_ASC, $index, SORT_ASC, $scripts);

            $nl = '';
            foreach ($scripts as $js) {
                if (!isset($js[1])) continue;
                $src = trim($js[1]);
                if ($src === '') continue;
                $javascript .= $nl.$src;
                $nl = PHP_EOL;
            }
        }

        // title 아래 CSS 삽입
        if ($stylesheet !== '' && $buffer !== '') {
            $buffer = preg_replace(
                '#(</title>)(?!.*</title>)#i',
                '$1'.$stylesheet,
                $buffer,
                1
            );
        }

        // </head><body> 앞 JS 삽입
        if ($javascript !== '' && $buffer !== '') {
            $buffer = preg_replace(
                '#</head>#i',
                $javascript.PHP_EOL.'</head>',
                $buffer,
                1
            );
        }

        return $buffer;
    }
}
// 로그인 후 이동할 URL
function login_url($url = '')
{
    if ($url === '') {
        $url = G5_URL;
    }

    $url = (string)$url;
    return urlencode(clean_xss_tags(urldecode($url)));
}

// $dir 을 포함하여 https 또는 http 주소를 반환
function https_url($dir, $https = true)
{
    $dir = ltrim($dir, '/');

    // common.php 최상단 G5_FORCE_HTTP 스위치: https 요청 자체를 무시하고 http 로 강제
    if (defined('G5_FORCE_HTTP') && G5_FORCE_HTTP) {
        $https = false;
    }

    if ($https) {
        if (defined('G5_HTTPS_DOMAIN') && G5_HTTPS_DOMAIN) {
            $url = G5_HTTPS_DOMAIN.'/'.$dir;
        } else {
            $url = G5_URL.'/'.$dir;
        }
    } else {
        if (defined('G5_DOMAIN') && G5_DOMAIN) {
            $url = G5_DOMAIN.'/'.$dir;
        } else {
            $url = G5_URL.'/'.$dir;
        }
    }

    // G5_URL 자체가 https 로 잡혀온 경우(서버 변수 오인식 등)에도 보정
    if (defined('G5_FORCE_HTTP') && G5_FORCE_HTTP) {
        $url = preg_replace('#^https://#i', 'http://', $url);
    }

    return $url;
}

// 게시판 공지 wr_id 추가/제거
function board_notice($bo_notice, $wr_id, $insert = false)
{
    $bo_notice = trim((string)$bo_notice);
    $wr_id = (int)$wr_id;

    $notice_array = $bo_notice !== '' ? explode(',', $bo_notice) : array();

    if ($insert && in_array($wr_id, $notice_array)) {
        return $bo_notice;
    }

    $notice_array = array_merge(array($wr_id), $notice_array);
    $notice_array = array_unique($notice_array);

    foreach ($notice_array as $key => $value) {
        if (!trim($value)) {
            unset($notice_array[$key]);
        }
    }

    if (!$insert) {
        foreach ($notice_array as $key => $value) {
            if ((int)$value === $wr_id) {
                unset($notice_array[$key]);
            }
        }
    }

    return implode(',', $notice_array);
}

// 임시 저장된 글 수
function autosave_count($mb_id)
{
    global $g5;

    $mb_id = trim($mb_id);
    if ($mb_id === '') {
        return 0;
    }

    $row = sql_fetch("select count(*) as cnt from {$g5['autosave_table']} where mb_id = '".sql_real_escape_string($mb_id)."'");
    return (int)$row['cnt'];
}

// get_sock: 간단 HTTP GET (PHP 8 호환)
if (!function_exists('get_sock')) {
    function get_sock($url)
    {
        $url = trim((string)$url);
        if ($url === '') {
            return '';
        }

        $matches = array();
        if (!preg_match('#^https?://([a-zA-Z0-9_\-\.]+)(:[0-9]+)?(.*)$#', $url, $matches)) {
            return '';
        }

        $host = $matches[1];
        $port = isset($matches[2]) && $matches[2] !== '' ? (int)substr($matches[2], 1) : 80;
        $get  = isset($matches[3]) && $matches[3] !== '' ? $matches[3] : '/';

        $errno  = 0;
        $errstr = '';
        $fp = @fsockopen($host, $port, $errno, $errstr, 30);
        if (!$fp) {
            return '';
        }

        $header = '';
        $body   = '';

        fwrite($fp, "GET {$get} HTTP/1.0\r\n");
        fwrite($fp, "Host: {$host}\r\n");
        fwrite($fp, "Connection: Close\r\n\r\n");

        // header
        while (!feof($fp)) {
            $line = fgets($fp, 1024);
            if ($line === false) {
                break;
            }
            $trim = trim($line);
            if ($trim === '') {
                break;
            }
            $header .= $line;
        }

        // body
        while (!feof($fp)) {
            $line = fgets($fp, 1024);
            if ($line === false) {
                break;
            }
            $body .= $line;
        }

        fclose($fp);

        return $body;
    }
}

// 인증, 결제 모듈 실행 체크
function module_exec_check($exe, $type)
{
    $error = '';
    $is_linux = (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN');

    if (!is_file($exe)) {
        $error = $exe.' 파일이 존재하지 않습니다.';
    } else if (!is_executable($exe)) {
        if ($is_linux) {
            $error = $exe.'\n파일의 실행권한이 없습니다.\n\nchmod 755 '.basename($exe).' 과 같이 실행권한을 부여해 주십시오.';
        } else {
            $error = $exe.'\n파일의 실행권한이 없습니다.\n\n'.basename($exe).' 파일에 실행권한을 부여해 주십시오.';
        }
    } else if ($is_linux) {
        $search   = false;
        $isbinary = true;

        $out = array();
        $return_var = 0;

        switch ($type) {
            case 'ct_cli':
                exec($exe.' -h 2>&1', $out, $return_var);
                if ($return_var == 139) {
                    $isbinary = false;
                    break;
                }
                foreach ($out as $line) {
                    if (strpos($line, 'KCP ENC') !== false) {
                        $search = true;
                        break;
                    }
                }
                break;

            case 'okname':
                exec($exe.' D 2>&1', $out, $return_var);
                if ($return_var == 139) {
                    $isbinary = false;
                    break;
                }
                foreach ($out as $line) {
                    if (strpos(strtolower($line), 'ret code') !== false) {
                        $search = true;
                        break;
                    }
                }
                break;
        }

        if (!$isbinary || !$search) {
            $error = $exe.'\n파일을 바이너리 타입으로 다시 업로드하여 주십시오.';
        }
    }

    if ($error !== '') {
        return '<script>alert("'.$error.'");</script>';
    }

    return '';
}

// input_vars 체크
function check_input_vars()
{
    $max_input_vars = (int)ini_get('max_input_vars');

    if ($max_input_vars > 0) {
        $post_vars   = count($_POST, COUNT_RECURSIVE);
        $get_vars    = count($_GET, COUNT_RECURSIVE);
        $cookie_vars = count($_COOKIE, COUNT_RECURSIVE);

        $input_vars = $post_vars + $get_vars + $cookie_vars;

        if ($input_vars > $max_input_vars) {
            alert('폼에서 전송된 변수의 개수가 max_input_vars 값보다 큽니다.\\n전송된 값중 일부는 유실되어 DB에 기록될 수 있습니다.\\n\\n문제를 해결하기 위해서는 서버 php.ini의 max_input_vars 값을 변경하십시오.');
        }
    }
}

// HTML 특수문자 변환
function htmlspecialchars2($str)
{
    $trans = array(
        '"'  => '&#034;',
        "'"  => '&#039;',
        '<'  => '&#060;',
        '>'  => '&#062;',
    );

    return strtr($str, $trans);
}

// date 형식 변환
function conv_date_format($format, $date, $add = '')
{
    $ts_base = strtotime($date);
    if ($ts_base === false) {
        return '';
    }

    if ($add !== '') {
        $timestamp = strtotime($add, $ts_base);
        if ($timestamp === false) {
            $timestamp = $ts_base;
        }
    } else {
        $timestamp = $ts_base;
    }

    return date($format, $timestamp);
}

// 검색어 특수문자 제거
function get_search_string($stx)
{
    $stx = (string)$stx;

    $stx_pattern = array(
        '#\.*/+#',
        '#\\\*#',
        '#\.{2,}#',
        '#[/\'\"%=*\#\(\)\|\+\&\!\$~\{\}\[\]`;:\?\^\,]+#',
    );

    $stx_replace = array('', '', '.', '');

    return preg_replace($stx_pattern, $stx_replace, $stx);
}

// XSS 관련 태그 제거
function clean_xss_tags($str)
{
    return preg_replace(
        '#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i',
        '',
        $str
    );
}

// \n, \r 정규화
function conv_unescape_nl($str)
{
    $search  = array('\\r', '\r', '\\n', '\n');
    $replace = array('', '', "\n", "\n");
    return str_replace($search, $replace, $str);
}

// 회원 삭제 마킹
function member_delete($mb_id)
{
    global $g5;

    $mb_id = trim($mb_id);
    if ($mb_id === '') return;

    $sql = "select mb_name, mb_nick, mb_ip, mb_memo, mb_level from {$g5['member_table']} where mb_id = '".sql_real_escape_string($mb_id)."'";
    $mb = sql_fetch($sql);

    if (!$mb || preg_match('#^[0-9]{8}.*삭제함#', (string)$mb['mb_memo'])) {
        return;
    }

    $memo = date('Ymd', G5_SERVER_TIME).' 삭제함'."\n".$mb['mb_memo'];
    $memo = sql_real_escape_string($memo);

    sql_query("update {$g5['member_table']} set mb_password = '', mb_level = 1, mb_memo = '{$memo}' where mb_id = '".sql_real_escape_string($mb_id)."'");
    sql_query("delete from {$g5['point_table']} where mb_id = '".sql_real_escape_string($mb_id)."'");
    sql_query("delete from {$g5['group_member_table']} where mb_id = '".sql_real_escape_string($mb_id)."'");
    sql_query("delete from {$g5['memo_table']} where me_recv_mb_id = '".sql_real_escape_string($mb_id)."' or me_send_mb_id = '".sql_real_escape_string($mb_id)."'");
    sql_query("delete from {$g5['scrap_table']} where mb_id = '".sql_real_escape_string($mb_id)."'");
    sql_query("update {$g5['group_table']} set gr_admin = '' where gr_admin = '".sql_real_escape_string($mb_id)."'");
    sql_query("update {$g5['board_table']} set bo_admin = '' where bo_admin = '".sql_real_escape_string($mb_id)."'");

    @unlink(G5_DATA_PATH.'/member/'.substr($mb_id, 0, 2).'/'.$mb_id.'.gif');

    if (function_exists('mdl_do_action')) {
        mdl_do_action('member_delete_after', $mb_id);
    }
}

// 파일명에서 특수문자 제거
function get_safe_filename($name)
{
    $name = (string)$name;
    $pattern = '/["\'<>=#&!%\\\\\(\)\*\+\?]/';
    return preg_replace($pattern, '', $name);
}

// 파일명 치환 (PHP 8 호환)
function replace_filename($name)
{
    @session_start();

    $name = (string)$name;
    $parts = explode('.', $name);
    $ext = '';

    if (count($parts) > 1) {
        $ext = array_pop($parts);
    }

    $ss_id = session_id();
    $usec  = get_microtime();
    $hash  = sha1($ss_id.(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '').$usec);

    if ($ext !== '') {
        return $hash.'.'.$ext;
    }

    return $hash;
}

// 아이코드 사용자정보
function get_icode_userinfo($id, $pass)
{
    $res = get_sock('http://www.icodekorea.com/res/userinfo.php?userid='.$id.'&userpw='.$pass);
    $res = explode(';', $res);

    return array(
        'code'    => isset($res[0]) ? $res[0] : '',
        'coin'    => isset($res[1]) ? $res[1] : '',
        'gpay'    => isset($res[2]) ? $res[2] : '',
        'payment' => isset($res[3]) ? $res[3] : '',
    );
}

// 문자열 암호화
function get_encrypt_string($str)
{
    if (defined('G5_STRING_ENCRYPT_FUNCTION') && G5_STRING_ENCRYPT_FUNCTION) {
        return call_user_func(G5_STRING_ENCRYPT_FUNCTION, $str);
    }

    return sql_password($str);
}

// 비밀번호 비교
function check_password($pass, $hash)
{
    if (defined('G5_STRING_ENCRYPT_FUNCTION') && G5_STRING_ENCRYPT_FUNCTION === 'create_hash') {
        return validate_password($pass, $hash);
    }

    $password = get_encrypt_string($pass);
    return hash_equals((string)$hash, (string)$password);
}

// 로그인 비밀번호 체크 및 마이그레이션
function login_password_check($mb, $pass, $hash)
{
    global $g5;

    $mb_id = isset($mb['mb_id']) ? $mb['mb_id'] : '';
    if ($mb_id === '') {
        return false;
    }

    if (defined('G5_STRING_ENCRYPT_FUNCTION')
        && G5_STRING_ENCRYPT_FUNCTION === 'create_hash'
        && (strlen($hash) === G5_MYSQL_PASSWORD_LENGTH || strlen($hash) === 16)
    ) {
        if (sql_password($pass) === $hash) {

            $new_password = create_hash($pass);
            $sql = "
                update {$g5['member_table']}
                   set mb_password = '".sql_real_escape_string($new_password)."',
                       mb_password2 = '".sql_real_escape_string($hash)."'
                 where mb_id = '".sql_real_escape_string($mb_id)."'
            ";
            sql_query($sql);

            return true;
        }
    }

    return check_password($pass, $hash);
}

// 동일한 host url 인지
function check_url_host($url, $msg = '', $return_url = G5_URL)
{
    if ($msg === '') {
        $msg = 'url에 타 도메인을 지정할 수 없습니다.';
    }

    $p = @parse_url($url);
    $host = preg_replace('/:[0-9]+$/', '', $_SERVER['HTTP_HOST']);

    if (strpos($url, 'http:') !== false) {
        if (!isset($p['scheme'], $p['host']) || !$p['scheme'] || !$p['host']) {
            alert('url 정보가 올바르지 않습니다.', $return_url);
        }
    }

    if ((isset($p['scheme']) && $p['scheme']) || (isset($p['host']) && $p['host'])) {
        if (isset($p['host']) && $p['host'] !== $host) {
            echo '<script>'.PHP_EOL;
            echo 'alert("'.$msg.'");'.PHP_EOL;
            echo 'document.location.href = "'.$return_url.'";'.PHP_EOL;
            echo '</script>'.PHP_EOL;
            echo '<noscript>'.PHP_EOL;
            echo '<p>'.$msg.'</p>'.PHP_EOL;
            echo '<p><a href="'.$return_url.'">돌아가기</a></p>'.PHP_EOL;
            echo '</noscript>'.PHP_EOL;
            exit;
        }
    }
}

// QUERY STRING 에 포함된 XSS 태그 제거
function clean_query_string($query, $amp = true)
{
    $qstr = trim((string)$query);
    parse_str($qstr, $out);

    if (is_array($out)) {
        $q = array();

        foreach ($out as $key => $val) {
            $key = strip_tags(trim($key));
            $val = trim($val);

            switch ($key) {
                case 'wr_id':
                case 'spt':
                case 'page':
                    $q[$key] = (int)preg_replace('/[^0-9]/', '', $val);
                    break;

                case 'sca':
                    $q[$key] = clean_xss_tags($val);
                    break;

                case 'sfl':
                case 'sst':
                    $q[$key] = preg_replace("/[\<\>'\"\\\%\=\(\)\s]/", "", $val);
                    break;

                case 'stx':
                    $q[$key] = get_search_string($val);
                    break;

                case 'sod':
                    $q[$key] = preg_match("/^(asc|desc)$/i", $val) ? $val : '';
                    break;

                case 'sop':
                    $q[$key] = preg_match("/^(or|and)$/i", $val) ? $val : '';
                    break;

                case 'w':
                    $q[$key] = substr($val, 0, 2);
                    break;

                case 'bo_table':
                    $val = preg_replace('/[^a-z0-9_]/i', '', $val);
                    $q[$key] = substr($val, 0, 20);
                    break;

                case 'gr_id':
                    $q[$key] = preg_replace('/[^a-z0-9_]/i', '', $val);
                    break;

                default:
                    $q[$key] = clean_xss_tags($val);
                    break;
            }
        }

        $sep = $amp ? '&amp;' : '&';
        return http_build_query($q, '', $sep);
    }

    return clean_xss_tags($qstr);
}

// 디바이스 전환 URL
function get_device_change_url()
{
    $p = @parse_url(G5_URL);
    if (!is_array($p) || !isset($p['scheme'], $p['host'])) {
        return G5_URL;
    }

    $href = $p['scheme'].'://'.$p['host'];
    if (isset($p['port']) && $p['port']) {
        $href .= ':'.$p['port'];
    }

    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $href .= $script;

    $q = array();
    $device = 'device='.(defined('G5_IS_MOBILE') && G5_IS_MOBILE ? 'pc' : 'mobile');

    if (!empty($_SERVER['QUERY_STRING'])) {
        foreach ($_GET as $key => $val) {
            if ($key === 'device') continue;

            $key = strip_tags($key);
            $val = strip_tags($val);

            if ($key !== '' && $val !== '') {
                $q[$key] = $val;
            }
        }
    }

    if (!empty($q)) {
        $query = http_build_query($q, '', '&amp;');
        $href .= '?'.$query.'&amp;'.$device;
    } else {
        $href .= '?'.$device;
    }

    return $href;
}

// 스킨 path
//   정책 (2026-05-07~):
//     - 입력은 항상 코어 스킨명 ('mmb', 'basic', 'qna', 'calculate', ...)
//     - theme/<name>/skin/ 는 화이트리스트 슬롯(THEME_SLOT_MAP)에 한해
//       활성 프리셋의 슬롯 테마가 자동으로 사용 — 사용자가 직접 선택하지 않음.
//     - module/<mod>/theme/, module/<mod>/skin/ 은 폐지.
//   레거시 안전:
//     - 'theme/<name>/<skin>' / 'mtheme/<mod>/<th>/<skin>' 같은 옛 저장값을 만나면
//       prefix 만 떼어내고 코어 스킨으로 흡수 → 자동 오버라이드가 알아서 처리.
function get_skin_path($dir, $skin)
{
    // 레거시 prefix 제거 — 옛 DB 값 호환 (theme/X/<skin>, mtheme/X/Y/<skin>, module/X/<skin>)
    $skin = (string)$skin;
    if (strncmp($skin, 'theme/', 6) === 0) {
        $rest = substr($skin, 6);
        $pos  = strrpos($rest, '/');
        if ($pos !== false) $skin = substr($rest, $pos + 1);
    } else if (strncmp($skin, 'mtheme/', 7) === 0) {
        $rest = substr($skin, 7);
        $pos  = strrpos($rest, '/');
        if ($pos !== false) $skin = substr($rest, $pos + 1);
    } else if (strncmp($skin, 'module/', 7) === 0) {
        $rest = substr($skin, 7);
        $pos  = strrpos($rest, '/');
        if ($pos !== false) $skin = substr($rest, $pos + 1);
    }

    $base = G5_SKIN_PATH.'/'.$dir.'/'.$skin;

    // 코어 스킨 — 활성 프리셋의 테마 슬롯에 자동 오버라이드 적용
    if (function_exists('theme_skin_dir') && isset($GLOBALS['THEME_SLOT_MAP'])) {
        if ($dir === 'board') {
            if (function_exists('theme_resolve_board_skin')) {
                $resolved = theme_resolve_board_skin($skin, $base);
                if ($resolved !== $base) return $resolved;
            }
        } else {
            $auto_slot = 'skin_'.$dir.'_'.$skin;
            if (isset($GLOBALS['THEME_SLOT_MAP'][$auto_slot])) {
                $resolved = theme_skin_dir($auto_slot, $base);
                if ($resolved !== $base) return $resolved;
            }
        }
    }

    return $base;
}

// 스킨 url
function get_skin_url($dir, $skin)
{
    $skin_path = get_skin_path($dir, $skin);
    $skin_path_url = str_replace(G5_PATH, G5_URL, $skin_path);
    if (function_exists('mdl_apply_filters')) {
        $skin_path_url = mdl_apply_filters('get_skin_url', $skin_path_url, $dir, $skin);
    }
    return $skin_path_url;
}

// 발신번호 유효성 체크
function check_vaild_callback($callback)
{
    $_callback = preg_replace('/[^0-9]/', '', (string)$callback);

    if (substr($_callback, 0, 4) === '1588' && strlen($_callback) != 8) return false;
    if (substr($_callback, 0, 2) === '02' && (strlen($_callback) != 9 && strlen($_callback) != 10)) return false;
    if (substr($_callback, 0, 3) === '030' && (strlen($_callback) != 10 && strlen($_callback) != 11)) return false;

    if (
        !preg_match("/^(02|0[3-6]\d|01(0|1|3|5|6|7|8|9)|070|080|007)\-?\d{3,4}\-?\d{4,5}$/", $_callback)
        && !preg_match("/^(15|16|18)\d{2}\-?\d{4,5}$/", $_callback)
    ) {
        return false;
    }

    if (preg_match("/^(02|0[3-6]\d|01(0|1|3|5|6|7|8|9)|070|080)\-?0{3,4}\-?\d{4}$/", $_callback)) {
        return false;
    }

    return true;
}

// 문자열 암복호화 (약한 XOR 방식, 기존 호환)
class str_encrypt
{
    var $salt;
    var $length;

    function __construct($salt = '')
    {
        if ($salt === '') {
            $this->salt = md5(G5_MYSQL_PASSWORD);
        } else {
            $this->salt = (string)$salt;
        }

        $this->length = strlen($this->salt);
    }

    function encrypt($str)
    {
        $str = (string)$str;
        $length = strlen($str);
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $char    = substr($str, $i, 1);
            $keychar = substr($this->salt, ($i % $this->length) - 1, 1);
            $char    = chr(ord($char) + ord($keychar));
            $result .= $char;
        }

        return base64_encode($result);
    }

    function decrypt($str)
    {
        $str = base64_decode($str);
        if ($str === false) return '';

        $length = strlen($str);
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $char    = substr($str, $i, 1);
            $keychar = substr($this->salt, ($i % $this->length) - 1, 1);
            $char    = chr(ord($char) - ord($keychar));
            $result .= $char;
        }

        return $result;
    }
}

// 글쓰기 토큰 발급
function get_write_token($bo_table)
{
    $token = md5(uniqid(mt_rand(), true));
    set_session('ss_write_'.$bo_table.'_token', $token);

    return $token;
}

// 글쓰기 토큰 검증 (현재 모든 요청 통과, 기존 주석 유지)
function check_write_token($bo_table)
{
    return true;
}

// include path에 data file 경로 포함 여부 체크
function is_include_path_check($path = '')
{
    if ($path === '') {
        return false;
    }

    if (preg_match('/\/data\/(file|editor)\/[A-Za-z0-9_]{1,20}\//', $path)) {
        return false;
    }

    return true;
}
?>