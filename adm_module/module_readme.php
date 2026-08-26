<?php
$sub_menu = '001100';
include_once('./_common.php');

auth_check($auth[$sub_menu], "r");

$name = isset($_GET['m']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['m']) : '';
if ($name === '') alert('모듈 이름이 지정되지 않았습니다.');

$readme_path = G5_PATH.'/module/'.$name.'/README.md';
if (!is_file($readme_path)) alert('README.md 파일이 없습니다: module/'.$name.'/README.md');

$raw = @file_get_contents($readme_path);
if ($raw === false) $raw = '';

// 매우 가벼운 마크다운 → HTML 변환 (의존성 없이)
function _mdl_md_inline($s) {
    // 인라인 코드
    $s = preg_replace_callback('/`([^`]+)`/', function ($m) {
        return '<code>'.htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8').'</code>';
    }, $s);
    // 굵게 **text**
    $s = preg_replace('/\*\*([^*]+)\*\*/u', '<strong>$1</strong>', $s);
    // 링크 [text](url)
    $s = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function ($m) {
        $t = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
        $u = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
        return '<a href="'.$u.'" target="_blank" rel="noopener">'.$t.'</a>';
    }, $s);
    return $s;
}

function _mdl_md_to_html($raw) {
    $lines = preg_split('/\r\n|\r|\n/', $raw);
    $out = array();
    $in_code = false;
    $in_list = false;
    $in_table = false;
    $table_buf = array();

    $flush_table = function () use (&$out, &$table_buf, &$in_table) {
        if (!$in_table) return;
        $rows = array();
        foreach ($table_buf as $tl) {
            $tl = trim($tl);
            if ($tl === '') continue;
            if (preg_match('/^\|?\s*:?-{3,}/', $tl)) continue;
            $tl = trim($tl, '|');
            $cells = array_map('trim', explode('|', $tl));
            $rows[] = $cells;
        }
        if ($rows) {
            $out[] = '<table class="tbl_head01" style="margin:8px 0;">';
            $first = true;
            foreach ($rows as $r) {
                $tag = $first ? 'th' : 'td';
                $out[] = '<tr>';
                foreach ($r as $c) {
                    $out[] = '<'.$tag.'>'._mdl_md_inline(htmlspecialchars($c, ENT_QUOTES, 'UTF-8')).'</'.$tag.'>';
                }
                $out[] = '</tr>';
                $first = false;
            }
            $out[] = '</table>';
        }
        $table_buf = array();
        $in_table = false;
    };

    $flush_list = function () use (&$out, &$in_list) {
        if ($in_list) { $out[] = '</ul>'; $in_list = false; }
    };

    foreach ($lines as $line) {
        // 코드 블록
        if (preg_match('/^```/', $line)) {
            $flush_list();
            $flush_table();
            if (!$in_code) { $out[] = '<pre style="background:#f7f7f7;border:1px solid #ddd;padding:10px;overflow:auto;"><code>'; $in_code = true; }
            else { $out[] = '</code></pre>'; $in_code = false; }
            continue;
        }
        if ($in_code) {
            $out[] = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
            continue;
        }
        // 표
        if (strpos($line, '|') !== false && trim($line) !== '') {
            $in_table = true;
            $table_buf[] = $line;
            continue;
        } else if ($in_table) {
            $flush_table();
        }
        // 헤딩
        if (preg_match('/^(#{1,6})\s+(.*)$/', $line, $m)) {
            $flush_list();
            $lvl = strlen($m[1]);
            $out[] = '<h'.$lvl.'>'._mdl_md_inline(htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8')).'</h'.$lvl.'>';
            continue;
        }
        // 리스트
        if (preg_match('/^\s*[-*]\s+(.*)$/', $line, $m)) {
            if (!$in_list) { $out[] = '<ul>'; $in_list = true; }
            $out[] = '<li>'._mdl_md_inline(htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8')).'</li>';
            continue;
        }
        $flush_list();
        // 빈 줄
        if (trim($line) === '') { $out[] = ''; continue; }
        // 단락
        $out[] = '<p>'._mdl_md_inline(htmlspecialchars($line, ENT_QUOTES, 'UTF-8')).'</p>';
    }
    if ($in_code) $out[] = '</code></pre>';
    $flush_list();
    $flush_table();
    return implode("\n", $out);
}

$html = _mdl_md_to_html($raw);

$g5['title'] = '모듈 README - '.$name;
include_once(G5_MDL_ADMIN_PATH.'/admin.head.php');
?>

<div class="local_desc01 local_desc">
    <p>모듈 <strong><?php echo h($name); ?></strong> 의 README.md 내용입니다. <small style="color:#888">(<?php echo h('module/'.$name.'/README.md'); ?>)</small></p>
    <p><a href="<?php echo G5_MDL_ADMIN_URL; ?>/module_list.php" class="btn_02">← 모듈 목록</a></p>
</div>

<div class="mdl_readme" style="background:#fff;padding:18px 20px;border:1px solid #e0e0e0;line-height:1.6;">
<?php echo $html; ?>
</div>
<?php
include_once(G5_MDL_ADMIN_PATH.'/admin.tail.php');
