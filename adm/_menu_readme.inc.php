<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 관리자 메뉴 설명서 (docs/adm/{코드}-*.md)
 */

function adm_menu_readme_dir()
{
    return G5_PATH.'/docs/adm';
}

function adm_menu_readme_file($menu_key)
{
    $menu_key = preg_replace('/[^0-9]/', '', (string)$menu_key);
    if ($menu_key === '' || strlen($menu_key) !== 3) {
        return '';
    }
    $files = glob(adm_menu_readme_dir().'/'.$menu_key.'-*.md');
    if (!is_array($files) || empty($files)) {
        return '';
    }
    return $files[0];
}

function adm_menu_readme_url($menu_key)
{
    $path = adm_menu_readme_file($menu_key);
    if ($path === '') {
        return '';
    }
    $menu_key = preg_replace('/[^0-9]/', '', (string)$menu_key);
    return G5_ADMIN_URL.'/menu_readme.php?m='.urlencode($menu_key);
}

function adm_menu_readme_info($menu_key)
{
    static $cache = array();
    $menu_key = preg_replace('/[^0-9]/', '', (string)$menu_key);
    if (isset($cache[$menu_key])) {
        return $cache[$menu_key];
    }

    $path = adm_menu_readme_file($menu_key);
    if ($path === '' || !is_file($path)) {
        return $cache[$menu_key] = null;
    }

    $raw = @file_get_contents($path);
    if ($raw === false) {
        $raw = '';
    }
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);

    return $cache[$menu_key] = array(
        'key'       => $menu_key,
        'path'      => $path,
        'file'      => basename($path),
        'url'       => adm_menu_readme_url($menu_key),
        'full_html' => adm_md_to_html($raw),
    );
}

function adm_md_slug($text)
{
    $text = html_entity_decode(strip_tags((string)$text), ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/[^\p{L}\p{N}\s_-]/u', '', $text);
    $text = preg_replace('/\s+/u', '-', trim($text));
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($text, 'UTF-8');
    }
    return strtolower($text);
}

function adm_md_href($url)
{
    $url = trim((string)$url);
    if (preg_match('/^([0-9]{3}-[A-Za-z0-9_-]+\.md)(#.*)?$/', $url, $m)) {
        $code = substr($m[1], 0, 3);
        $hash = isset($m[2]) ? $m[2] : '';
        return G5_ADMIN_URL.'/menu_readme.php?m='.rawurlencode($code).$hash;
    }
    return $url;
}

function adm_md_inline($s)
{
    // $s 는 호출 측에서 이미 htmlspecialchars 된 문자열.
    // 코드·링크 안을 다시 이스케이프하면 `&lt;` 가 화면에 그대로 보인다.
    $s = preg_replace_callback('/`([^`]+)`/', function ($m) {
        return '<code>'.$m[1].'</code>';
    }, $s);

    $s = preg_replace('/\*\*([^*]+)\*\*/u', '<strong>$1</strong>', $s);

    $s = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function ($m) {
        $raw_url = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
        $t = $m[1];
        $u = htmlspecialchars(adm_md_href($raw_url), ENT_QUOTES, 'UTF-8');
        $ext = (strpos($raw_url, '://') !== false) ? ' target="_blank" rel="noopener"' : '';
        return '<a href="'.$u.'"'.$ext.'>'.$t.'</a>';
    }, $s);

    return $s;
}

function adm_md_to_html($raw)
{
    $lines = preg_split('/\r\n|\r|\n/', (string)$raw);
    $out = array();
    $in_code = false;
    $in_list = false;
    $list_tag = '';
    $in_table = false;
    $table_buf = array();

    $flush_table = function () use (&$out, &$table_buf, &$in_table) {
        if (!$in_table) {
            return;
        }
        $rows = array();
        foreach ($table_buf as $tl) {
            $tl = trim($tl);
            if ($tl === '') {
                continue;
            }
            if (preg_match('/^\|?\s*:?-{3,}/', $tl)) {
                continue;
            }
            $tl = trim($tl, '|');
            $cells = array_map('trim', explode('|', $tl));
            $rows[] = $cells;
        }
        if ($rows) {
            $out[] = '<table>';
            $first = true;
            foreach ($rows as $r) {
                $tag = $first ? 'th' : 'td';
                $out[] = '<tr>';
                foreach ($r as $c) {
                    $out[] = '<'.$tag.'>'.adm_md_inline(htmlspecialchars($c, ENT_QUOTES, 'UTF-8')).'</'.$tag.'>';
                }
                $out[] = '</tr>';
                $first = false;
            }
            $out[] = '</table>';
        }
        $table_buf = array();
        $in_table = false;
    };

    $flush_list = function () use (&$out, &$in_list, &$list_tag) {
        if ($in_list) {
            $out[] = '</'.$list_tag.'>';
            $in_list = false;
            $list_tag = '';
        }
    };

    foreach ($lines as $line) {
        if (preg_match('/^```/', $line)) {
            $flush_list();
            $flush_table();
            if (!$in_code) {
                $out[] = '<pre><code>';
                $in_code = true;
            } else {
                $out[] = '</code></pre>';
                $in_code = false;
            }
            continue;
        }
        if ($in_code) {
            $out[] = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
            continue;
        }

        if (strpos($line, '|') !== false && trim($line) !== '') {
            $in_table = true;
            $table_buf[] = $line;
            continue;
        }
        if ($in_table) {
            $flush_table();
        }

        if (preg_match('/^(#{1,6})\s+(.*)$/', $line, $m)) {
            $flush_list();
            $lvl = strlen($m[1]);
            $title = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
            $id = htmlspecialchars(adm_md_slug($m[2]), ENT_QUOTES, 'UTF-8');
            $out[] = '<h'.$lvl.' id="'.$id.'">'.adm_md_inline($title).'</h'.$lvl.'>';
            continue;
        }

        if (preg_match('/^---+\s*$/', $line)) {
            $flush_list();
            $out[] = '<hr>';
            continue;
        }

        if (preg_match('/^\s*[-*]\s+(.*)$/', $line, $m)) {
            if (!$in_list || $list_tag !== 'ul') {
                $flush_list();
                $out[] = '<ul>';
                $in_list = true;
                $list_tag = 'ul';
            }
            $out[] = '<li>'.adm_md_inline(htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8')).'</li>';
            continue;
        }

        if (preg_match('/^\s*\d+\.\s+(.*)$/', $line, $m)) {
            if (!$in_list || $list_tag !== 'ol') {
                $flush_list();
                $out[] = '<ol>';
                $in_list = true;
                $list_tag = 'ol';
            }
            $out[] = '<li>'.adm_md_inline(htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8')).'</li>';
            continue;
        }

        $flush_list();

        if (trim($line) === '') {
            $out[] = '';
            continue;
        }

        $out[] = '<p>'.adm_md_inline(htmlspecialchars($line, ENT_QUOTES, 'UTF-8')).'</p>';
    }

    if ($in_code) {
        $out[] = '</code></pre>';
    }
    $flush_list();
    $flush_table();

    return implode("\n", $out);
}
