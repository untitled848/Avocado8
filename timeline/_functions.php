<?php
/**
 * 타임라인 함수 라이브러리
 * timeline/_common.php 및 관리자 타임라인 메뉴에서만 로드한다. (extend 전역 include 아님)
 *
 * 테이블 구조:
 * - {prefix}timeline: 타임라인 리스트 (board 테이블 대응)
 * - {prefix}timeline_{tl_table}: 개별 글 테이블 (write 테이블 대응)
 * - {prefix}timeline_good / bookmark / mention / temp / user
 * - {prefix}timeline_dm_* : DM
 */
if (!defined('_GNUBOARD_')) exit;
if (defined('G5_TIMELINE_LIB')) return;
define('G5_TIMELINE_LIB', true);

// =====================================================
// 테이블 정의 (G5_TABLE_PREFIX 정의 후 실행)
// =====================================================

$g5['timeline_table'] = G5_TABLE_PREFIX.'timeline';
$g5['timeline_list_table'] = $g5['timeline_table'];
$g5['timeline_good_table'] = G5_TABLE_PREFIX.'timeline_good'; 
$g5['timeline_bookmark_table'] = G5_TABLE_PREFIX.'timeline_bookmark';
$g5['timeline_mention_table'] = G5_TABLE_PREFIX.'timeline_mention';
$g5['timeline_user_table'] = G5_TABLE_PREFIX.'timeline_user';
$g5['timeline_temp_table'] = G5_TABLE_PREFIX.'timeline_temp';
$g5['timeline_write_prefix'] = G5_TABLE_PREFIX.'timeline_';
$g5['timeline_dm_rooms'] = G5_TABLE_PREFIX.'timeline_dm_rooms';
$g5['timeline_dm_participants'] = G5_TABLE_PREFIX.'timeline_dm_participants';
$g5['timeline_dm_messages'] = G5_TABLE_PREFIX.'timeline_dm_messages';
$g5['timeline_dm_read'] = G5_TABLE_PREFIX.'timeline_dm_read';

/**
 * 타임라인 목록 테이블 존재 여부
 */
function timeline_list_table_exists() {
    global $g5;
    static $cached = null;
    if ($cached !== null) return $cached;
    $table = !empty($g5['timeline_list_table']) ? $g5['timeline_list_table'] : '';
    if ($table === '') {
        $cached = false;
        return false;
    }
    $row = sql_fetch("SHOW TABLES LIKE '{$table}'", false);
    $cached = !empty($row);
    return $cached;
}

/**
 * 관리자 타임라인 메뉴: 목록 테이블이 없으면 사이트 DB 점검/보정으로 이동
 */
function timeline_admin_require_installed($as_json = false) {
    if (timeline_list_table_exists()) return true;
    $url = G5_ADMIN_URL.'/db_check.php';
    if ($as_json) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(array(
            'error' => '타임라인 데이터베이스가 설치되지 않았습니다. 사이트 DB 점검/보정에서 설치하세요.',
            'redirect' => $url,
        ), JSON_UNESCAPED_UNICODE);
        exit;
    }
    goto_url($url);
}


// 멘션 알림 종류 시드 (기존 DB에도 mention 타입 보장)
if (function_exists('ensure_call_type') && !empty($g5['call_type_map_table'])) {
    $__ct_map = sql_fetch("SHOW TABLES LIKE '{$g5['call_type_map_table']}'", false);
    if ($__ct_map) {
        ensure_call_type(
            'mention',
            '멘션',
            '타임라인에서 멘션되었습니다.',
            '/timeline/view.php?tl_table={tl_table}&wr_id={wr_id}',
            1
        );
    }
    unset($__ct_map);
}



// 타임라인 이미지 경로 상수
if (defined('G5_DATA_PATH') && !defined('G5_TIMELINE_PATH')) {
    define('G5_TIMELINE_PATH', G5_DATA_PATH.'/timeline');
}
if (defined('G5_DATA_URL') && !defined('G5_TIMELINE_URL')) {
    define('G5_TIMELINE_URL', G5_DATA_URL.'/timeline');
}


// =====================================================
// 타임라인 관리 함수
// =====================================================

/**
 * 타임라인 정보 조회
 * @param string $tl_table 타임라인 테이블명
 * @return array 타임라인 설정 배열
 */
function get_timeline($tl_table) {
    global $g5;
    $tl_table = sql_escape_string($tl_table);
    return sql_fetch("SELECT * FROM {$g5['timeline_table']} WHERE tl_table = '{$tl_table}'");
}


/**
 * 타임라인 목록 조회
 * @return array 타임라인 목록
 */
function get_timeline_list_all() {
    global $g5;
    $result = sql_query("SELECT * FROM {$g5['timeline_table']} ORDER BY tl_table");
    $list = array();
    while ($row = sql_fetch_array($result)) {
        $list[] = $row;
    }
    return $list;
}


/**
 * 타임라인 write 테이블명 반환
 * @param string $tl_table 타임라인 테이블명
 * @return string
 */
function get_timeline_write_table($tl_table) {
    global $g5;
    return $g5['timeline_write_prefix'].$tl_table;
}


/**
 * 타임라인 write 테이블 스키마 (avo_timeline_default / ktester.sql 기준)
 * - 새 타임라인 생성·기존 테이블 보정 공통 소스
 * - wr_event_log, wr_mon_*, ma_id 는 모듈 전용이라 포함하지 않음
 * @return array 순서 유지: col => definition
 */
function get_timeline_write_schema() {
    return array(
        'wr_id'             => "int(11) NOT NULL AUTO_INCREMENT COMMENT '글 ID'",
        'wr_num'            => "int(11) NOT NULL DEFAULT 0 COMMENT '글 그룹 번호 (음수, 범프용)'",
        'wr_parent'         => "int(11) NOT NULL DEFAULT 0 COMMENT '부모글 ID (원글 ID, 원글은 음수)'",
        'wr_is_comment'     => "tinyint(1) NOT NULL DEFAULT 0 COMMENT '댓글 여부 (원글=0, 댓글=1)'",
        'wr_comment'        => "int(11) NOT NULL DEFAULT 0 COMMENT '댓글/답글 수'",
        'wr_mention_root'   => "int(11) NOT NULL DEFAULT 0 COMMENT '멘션 타래 루트 (원글=0, 1차댓글=1~N, 2차이상=부모와동일)'",
        'wr_reply_to'       => "int(11) NOT NULL DEFAULT 0 COMMENT '답장 대상 글 ID (직전 댓글)'",
        'mb_id'             => "varchar(20) NOT NULL DEFAULT '' COMMENT '회원 ID'",
        'ch_id'             => "int(11) NOT NULL DEFAULT 0 COMMENT '캐릭터 ID'",
        'wr_name'           => "varchar(255) NOT NULL DEFAULT '' COMMENT '작성자명 (캐릭터명)'",
        'wr_content'        => "text DEFAULT NULL COMMENT '글 내용'",
        'wr_file'           => "tinyint(4) NOT NULL DEFAULT 0 COMMENT '첨부파일 개수'",
        'wr_image1'         => "varchar(255) NOT NULL DEFAULT '' COMMENT '첨부 이미지1'",
        'wr_image2'         => "varchar(255) NOT NULL DEFAULT '' COMMENT '첨부 이미지2'",
        'wr_image3'         => "varchar(255) NOT NULL DEFAULT '' COMMENT '첨부 이미지3'",
        'wr_image4'         => "varchar(255) NOT NULL DEFAULT '' COMMENT '첨부 이미지4'",
        'wr_good'           => "int(11) NOT NULL DEFAULT 0 COMMENT '좋아요 수'",
        'wr_log'            => "text DEFAULT NULL COMMENT '액션 로그'",
        'wr_item'           => "varchar(20) NOT NULL DEFAULT '' COMMENT '사용 아이템 ID'",
        'wr_item_log'       => "text DEFAULT NULL COMMENT '아이템 사용 로그'",
        'wr_dice'           => "varchar(255) NOT NULL DEFAULT ''",
        'wr_dice1'          => "tinyint(4) NOT NULL DEFAULT 0 COMMENT '주사위1'",
        'wr_dice2'          => "tinyint(4) NOT NULL DEFAULT 0 COMMENT '주사위2'",
        'wr_like_count'     => "int(11) NOT NULL DEFAULT 0 COMMENT '좋아요 수'",
        'wr_bookmark_count' => "int(11) NOT NULL DEFAULT 0 COMMENT '북마크 수'",
        'wr_reply_count'    => "int(11) NOT NULL DEFAULT 0 COMMENT '답글 수'",
        'wr_datetime'       => "datetime DEFAULT NULL COMMENT '작성일시'",
        'wr_last'           => "varchar(19) NOT NULL DEFAULT '' COMMENT '최종 수정일'",
        'wr_bump_datetime'  => "datetime DEFAULT NULL COMMENT '범프일시'",
        'wr_ip'             => "varchar(50) NOT NULL DEFAULT '' COMMENT '작성 IP'",
        'wr_deleted'        => "tinyint(4) NOT NULL DEFAULT 0 COMMENT '삭제 여부'",
        'cf_time'           => "int(11) NOT NULL DEFAULT 0 COMMENT '작성 시점 시기(時期)'",
        'ti_id'             => "int(11) NOT NULL DEFAULT 0 COMMENT '작성 시점 타이틀(캐릭터 타이틀)'",
        'wr_img_hidden'     => "tinyint(1) NOT NULL DEFAULT 0",
        'wr_text_hidden'    => "tinyint(1) NOT NULL DEFAULT 0",
        'wr_modified'       => "tinyint(1) NOT NULL DEFAULT 0",
        'wr_waring_text'    => "varchar(255) NOT NULL DEFAULT ''",
        'wr_thumb'          => "varchar(255) NOT NULL DEFAULT ''",
        'wr_1'              => "varchar(255) NOT NULL DEFAULT ''",
        'wr_2'              => "varchar(255) NOT NULL DEFAULT ''",
        'wr_3'              => "varchar(255) NOT NULL DEFAULT ''",
        'wr_4'              => "varchar(255) NOT NULL DEFAULT ''",
        'wr_5'              => "varchar(255) NOT NULL DEFAULT ''",
        'wr_6'              => "varchar(255) NOT NULL DEFAULT ''",
        'wr_7'              => "varchar(255) NOT NULL DEFAULT ''",
        'wr_8'              => "varchar(255) NOT NULL DEFAULT ''",
        'wr_9'              => "varchar(255) NOT NULL DEFAULT ''",
        'wr_10'             => "varchar(255) NOT NULL DEFAULT ''",
        'wr_script'         => "int(10) NOT NULL DEFAULT 0",
    );
}

/**
 * 타임라인 write 테이블 인덱스 (avo_timeline_default / ktester.sql 기준)
 * @return array name => column list
 */
function get_timeline_write_indexes() {
    return array(
        'idx_wr_num'           => '(`wr_num`)',
        'idx_wr_parent'        => '(`wr_parent`)',
        'idx_mb_id'            => '(`mb_id`)',
        'idx_ch_id'            => '(`ch_id`)',
        'idx_wr_is_comment'    => '(`wr_is_comment`)',
        'idx_wr_datetime'      => '(`wr_datetime`)',
        'idx_wr_last'          => '(`wr_last`)',
        'idx_wr_bump_datetime' => '(`wr_bump_datetime`)',
        'idx_wr_mention_root'  => '(`wr_parent`, `wr_mention_root`)',
        'idx_wr_reply_to'      => '(`wr_reply_to`)',
    );
}

/**
 * 타임라인 테이블 생성
 * @param string $tl_table 타임라인 테이블명
 * @return bool
 */
function create_timeline_write_table($tl_table) {
    $write_table = get_timeline_write_table($tl_table);

    $exists = sql_fetch("SHOW TABLES LIKE '{$write_table}'");
    if ($exists) {
        return true;
    }

    $cols_sql = array();
    foreach (get_timeline_write_schema() as $col => $def) {
        $cols_sql[] = "`{$col}` {$def}";
    }
    $idx_sql = array('PRIMARY KEY (`wr_id`)');
    foreach (get_timeline_write_indexes() as $idx => $cols) {
        $idx_sql[] = "KEY `{$idx}` {$cols}";
    }

    $sql = "CREATE TABLE IF NOT EXISTS `{$write_table}` (
        ".implode(",\n        ", $cols_sql).",
        ".implode(",\n        ", $idx_sql)."
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='타임라인 기본'";

    return sql_query($sql);
}

/**
 * 타임라인 write 테이블 필수 컬럼/인덱스 보장 (멱등)
 * 관리자·마이그레이션 등에서 필요 시 호출. 프론트/AJAX에서는 호출하지 않는다.
 */
function ensure_timeline_write_columns($tl_table) {
    if ($tl_table === '' || $tl_table === null) return false;
    $write_table = get_timeline_write_table($tl_table);
    $exists = sql_fetch("SHOW TABLES LIKE '{$write_table}'");
    if (!$exists) return false;

    $existing = array();
    $rs = sql_query("SHOW COLUMNS FROM `{$write_table}`", false);
    if ($rs) {
        while ($row = sql_fetch_array($rs)) {
            $existing[strtolower($row['Field'])] = $row;
        }
    }

    $schema = get_timeline_write_schema();
    $prev = '';
    foreach ($schema as $col => $def) {
        $key = strtolower($col);
        if ($col === 'wr_id') {
            $prev = $col;
            continue;
        }
        if (!isset($existing[$key])) {
            $sql = "ALTER TABLE `{$write_table}` ADD COLUMN `{$col}` {$def}";
            if ($prev !== '') {
                $sql .= " AFTER `{$prev}`";
            }
            sql_query($sql, false);
            $existing[$key] = array('Field' => $col);
        }
        $prev = $col;
    }

    // wr_script: ktester 기준 int(10), 구 tinyint(1) 보정
    if (isset($existing['wr_script'])) {
        $type = isset($existing['wr_script']['Type']) ? strtolower($existing['wr_script']['Type']) : '';
        if ($type !== '' && strpos($type, 'int(10)') === false && strpos($type, 'tinyint') !== false) {
            sql_query("ALTER TABLE `{$write_table}` MODIFY `wr_script` int(10) NOT NULL DEFAULT 0 AFTER `wr_10`", false);
        }
    }

    // wr_bump_datetime: ktester 기준 NULL 허용
    if (isset($existing['wr_bump_datetime']) && isset($existing['wr_bump_datetime']['Null'])
        && strtoupper($existing['wr_bump_datetime']['Null']) === 'NO') {
        sql_query("ALTER TABLE `{$write_table}` MODIFY `wr_bump_datetime` datetime DEFAULT NULL COMMENT '범프일시'", false);
    }

    foreach (get_timeline_write_indexes() as $idx => $cols) {
        $has_idx = sql_fetch("SHOW INDEX FROM `{$write_table}` WHERE Key_name = '{$idx}'", false);
        if (empty($has_idx)) {
            sql_query("ALTER TABLE `{$write_table}` ADD INDEX `{$idx}` {$cols}", false);
        }
    }
    return true;
}

/**
 * 글 저장용 작성 시점 썸네일 URL
 * get_timeline_thumb 결과, 없으면 no_profile.png
 */
function get_timeline_post_thumb_url($ch_id) {
    $ch_id = (int)$ch_id;
    $url = '';
    if ($ch_id > 0 && function_exists('get_timeline_thumb')) {
        $url = get_timeline_thumb($ch_id, 1);
    }
    if (!$url) {
        $url = G5_IMG_URL.'/no_profile.png';
    }
    return $url;
}

/**
 * 썸네일 URL이 로컬 파일이면 경로 반환, 외부 URL이면 null
 */
function timeline_thumb_url_to_local_path($url) {
    $url = trim((string)$url);
    if ($url === '') return null;

    if (defined('G5_DATA_URL') && defined('G5_DATA_PATH') && strpos($url, G5_DATA_URL) === 0) {
        return G5_DATA_PATH.substr($url, strlen(G5_DATA_URL));
    }
    if (defined('G5_URL') && defined('G5_PATH') && strpos($url, G5_URL) === 0) {
        return G5_PATH.substr($url, strlen(G5_URL));
    }
    // 사이트 루트 상대경로 (/data/...)
    if (isset($url[0]) && $url[0] === '/' && strpos($url, '//') !== 0) {
        if (defined('G5_PATH')) {
            return G5_PATH.$url;
        }
    }
    return null;
}

/**
 * 업로드 이미지가 max 픽셀을 넘으면 비율 유지로 축소해 같은 경로에 덮어씀.
 * 애니 GIF는 원본 유지. 이미 작으면 변경 없이 true.
 *
 * @param string $file_path
 * @param int $max_w
 * @param int $max_h
 * @param int $quality JPEG/WebP 품질 (1–100)
 * @return bool 성공 또는 리사이즈 불필요
 */
function timeline_resize_uploaded_image($file_path, $max_w = 800, $max_h = 800, $quality = 85) {
    if (!is_file($file_path) || !function_exists('imagecreatetruecolor')) {
        return false;
    }

    $max_w = (int)$max_w;
    $max_h = (int)$max_h;
    $quality = (int)$quality;
    if ($max_w < 1 || $max_h < 1) {
        return false;
    }
    if ($quality < 1) $quality = 1;
    if ($quality > 100) $quality = 100;

    $size = @getimagesize($file_path);
    if (!$size || empty($size[0]) || empty($size[1])) {
        return false;
    }

    $w = (int)$size[0];
    $h = (int)$size[1];
    $type = (int)$size[2];

    if ($w <= $max_w && $h <= $max_h) {
        return true;
    }

    if ($type === IMAGETYPE_GIF) {
        if (!function_exists('is_animated_gif') && defined('G5_LIB_PATH')) {
            @include_once(G5_LIB_PATH.'/thumbnail.lib.php');
        }
        if (function_exists('is_animated_gif') && is_animated_gif($file_path)) {
            return true;
        }
    }

    $ratio = min($max_w / $w, $max_h / $h);
    $nw = max(1, (int)round($w * $ratio));
    $nh = max(1, (int)round($h * $ratio));

    $src = null;
    $degree = 0;

    switch ($type) {
        case IMAGETYPE_GIF:
            $src = @imagecreatefromgif($file_path);
            break;
        case IMAGETYPE_JPEG:
            $src = @imagecreatefromjpeg($file_path);
            if ($src !== false && function_exists('exif_read_data')) {
                $exif = @exif_read_data($file_path);
                if (!empty($exif['Orientation'])) {
                    switch ((int)$exif['Orientation']) {
                        case 8: $degree = 90; break;
                        case 3: $degree = 180; break;
                        case 6: $degree = -90; break;
                    }
                    if ($degree) {
                        $rotated = @imagerotate($src, $degree, 0);
                        if ($rotated) {
                            imagedestroy($src);
                            $src = $rotated;
                            if ($degree === 90 || $degree === -90) {
                                $tmp = $w;
                                $w = $h;
                                $h = $tmp;
                                $ratio = min($max_w / $w, $max_h / $h);
                                $nw = max(1, (int)round($w * $ratio));
                                $nh = max(1, (int)round($h * $ratio));
                            }
                        }
                    }
                }
            }
            break;
        case IMAGETYPE_PNG:
            $src = @imagecreatefrompng($file_path);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagecreatefromwebp')) {
                $src = @imagecreatefromwebp($file_path);
            }
            break;
        default:
            return false;
    }

    if (!$src) {
        return false;
    }

    $dst = @imagecreatetruecolor($nw, $nh);
    if (!$dst) {
        imagedestroy($src);
        return false;
    }

    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF || $type === IMAGETYPE_WEBP) {
        @imagealphablending($dst, false);
        @imagesavealpha($dst, true);
        $transparent = @imagecolorallocatealpha($dst, 0, 0, 0, 127);
        if ($transparent !== false) {
            @imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
        }
    }

    @imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    $ok = false;
    switch ($type) {
        case IMAGETYPE_GIF:
            $ok = @imagegif($dst, $file_path);
            break;
        case IMAGETYPE_JPEG:
            $ok = @imagejpeg($dst, $file_path, $quality);
            break;
        case IMAGETYPE_PNG:
            // PNG compression 0–9 (quality 100 → 0)
            $png_level = (int)round((100 - $quality) / 11.111);
            if ($png_level < 0) $png_level = 0;
            if ($png_level > 9) $png_level = 9;
            $ok = @imagepng($dst, $file_path, $png_level);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagewebp')) {
                $ok = @imagewebp($dst, $file_path, $quality);
            }
            break;
    }

    imagedestroy($src);
    imagedestroy($dst);

    return (bool)$ok;
}

/**
 * 글 행에서 표시용 작성자명 (작성 시점 wr_name)
 * 구 데이터에 wr_name이 비어 있으면 ch_name 폴백
 */
function get_timeline_row_name($row, $fallback = '') {
    if (!is_array($row)) {
        return (string)$fallback;
    }
    $name = ses($row, 'wr_name', '', 'raw');
    if ($name === '') {
        $name = ses($row, 'ch_name', '', 'raw');
    }
    if ($name === '') {
        $name = (string)$fallback;
    }
    return $name;
}

/**
 * 글 행에서 표시용 썸네일 (작성 시점 wr_thumb)
 * 로컬 파일이 없으면 no_profile.png
 */
function get_timeline_row_thumb($row) {
    $fallback = G5_IMG_URL.'/no_profile.png';
    $thumb = '';
    if (is_array($row)) {
        $thumb = ses($row, 'wr_thumb', '', 'raw');
    } else {
        $thumb = (string)$row;
    }
    if ($thumb === '') {
        return $fallback;
    }

    $local = timeline_thumb_url_to_local_path($thumb);
    if ($local !== null && !is_file($local)) {
        return $fallback;
    }
    return $thumb;
}


// =====================================================
// 타임라인 사용자 권한 함수
// =====================================================

/**
 * 타임라인 사용자 테이블 생성 / 프로필 컬럼 보장
 * @return bool
 */
function create_timeline_user_table() {
    // 테이블 설치는 install/gnuboard5.sql · adm/db_check.php 에서 수행
    return true;
}


// =====================================================
// 타임라인 답글 임시저장
// =====================================================

/**
 * 답글 임시저장 테이블 생성 / 컬럼 보장
 * @return bool
 */
function create_timeline_temp_table() {
    // 테이블 설치는 install/gnuboard5.sql · adm/db_check.php 에서 수행
    return true;
}

/**
 * 임시저장용 tu_id (현재 활성 캐릭터 ID)
 * @param int $ch_id
 * @return int
 */
function get_timeline_temp_tu_id($ch_id = 0) {
    global $character;
    $ch_id = (int)$ch_id;
    if ($ch_id <= 0) {
        $ch_id = (int)ses($character, 'ch_id', 0, 'int');
    }
    return $ch_id;
}

/**
 * 임시저장 대상 wr_id 확인 (원글/댓글 모두 그 wr_id 그대로 사용)
 * @param string $tl_table
 * @param int $wr_id
 * @return int
 */
function resolve_timeline_temp_parent_wr_id($tl_table, $wr_id) {
    $wr_id = (int)$wr_id;
    if ($wr_id <= 0) {
        return 0;
    }
    $write_table = get_timeline_write_table($tl_table);
    $row = sql_fetch("SELECT wr_id FROM {$write_table} WHERE wr_id = '{$wr_id}'");
    if (!$row || empty($row['wr_id'])) {
        return 0;
    }
    return (int)$row['wr_id'];
}

/**
 * 임시저장 행을 API 반환 형태로
 * @param array|null $row
 * @return array
 */
function timeline_temp_row_payload($row) {
    $content = '';
    if (is_array($row)) {
        $content = (string)ses($row, 'wr_content', '', 'raw');
    }
    return array(
        'tt_id'         => is_array($row) ? (int)ses($row, 'tt_id', 0, 'int') : 0,
        'tl_table'      => is_array($row) ? (string)ses($row, 'tl_table', '', 'raw') : '',
        'parent_wr_id'  => is_array($row) ? (int)ses($row, 'parent_wr_id', 0, 'int') : 0,
        'tu_id'         => is_array($row) ? (int)ses($row, 'tu_id', 0, 'int') : 0,
        'wr_content'    => $content,
    );
}

/**
 * 답글 임시저장 조회 (원본 텍스트, 렌더링 없음)
 * @param string $tl_table
 * @param int $parent_wr_id
 * @param int $tu_id
 * @return array|null
 */
function get_timeline_temp($tl_table, $parent_wr_id, $tu_id = 0) {
    global $g5;

    $tu_id = get_timeline_temp_tu_id($tu_id);
    $parent_wr_id = resolve_timeline_temp_parent_wr_id($tl_table, $parent_wr_id);
    if ($tu_id <= 0 || $parent_wr_id <= 0 || $tl_table === '') {
        return null;
    }

    $tl_esc = sql_escape_string($tl_table);
    $row = sql_fetch("
        SELECT tt_id, tl_table, parent_wr_id, tu_id, wr_content
        FROM {$g5['timeline_temp_table']}
        WHERE tl_table = '{$tl_esc}'
          AND parent_wr_id = '{$parent_wr_id}'
          AND tu_id = '{$tu_id}'
        LIMIT 1
    ");

    if (!$row || empty($row['tt_id'])) {
        return null;
    }
    if (trim((string)ses($row, 'wr_content', '', 'raw')) === '') {
        return null;
    }
    return $row;
}

/**
 * 답글 임시저장 (원본 텍스트 upsert)
 * @param string $tl_table
 * @param int $parent_wr_id
 * @param string $wr_content
 * @param int $tu_id
 * @return array
 */
function save_timeline_temp($tl_table, $parent_wr_id, $wr_content, $tu_id = 0) {
    global $g5;


    $result = array('success' => false, 'message' => '', 'row' => null);
    $tu_id = get_timeline_temp_tu_id($tu_id);
    $parent_wr_id = resolve_timeline_temp_parent_wr_id($tl_table, $parent_wr_id);
    $wr_content = trim(stripslashes((string)$wr_content));

    if ($tu_id <= 0) {
        $result['message'] = '로그인이 필요합니다.';
        return $result;
    }
    if ($parent_wr_id <= 0 || $tl_table === '') {
        $result['message'] = '답글 대상 글 정보가 없습니다.';
        return $result;
    }
    if ($wr_content === '') {
        $result['message'] = '저장할 내용이 없습니다.';
        return $result;
    }

    $tl_esc = sql_escape_string($tl_table);
    $content_esc = sql_escape_string($wr_content);
    $time = G5_TIME_YMDHIS;

    sql_query("
        INSERT INTO {$g5['timeline_temp_table']}
        SET tl_table = '{$tl_esc}',
            parent_wr_id = '{$parent_wr_id}',
            tu_id = '{$tu_id}',
            wr_content = '{$content_esc}',
            tt_datetime = '{$time}'
        ON DUPLICATE KEY UPDATE
            wr_content = '{$content_esc}',
            tt_datetime = '{$time}'
    ");

    $row = get_timeline_temp($tl_table, $parent_wr_id, $tu_id);
    if (!$row) {
        $result['message'] = '저장에 실패했습니다.';
        return $result;
    }

    $result['success'] = true;
    $result['message'] = '임시저장되었습니다.';
    $result['row'] = $row;
    return $result;
}

/**
 * 답글 임시저장 삭제
 * @param string $tl_table
 * @param int $parent_wr_id
 * @param int $tu_id
 * @return bool
 */
function delete_timeline_temp($tl_table, $parent_wr_id, $tu_id = 0) {
    global $g5;

    $tu_id = get_timeline_temp_tu_id($tu_id);
    $parent_wr_id = resolve_timeline_temp_parent_wr_id($tl_table, $parent_wr_id);
    if ($tu_id <= 0 || $parent_wr_id <= 0 || $tl_table === '') {
        return false;
    }

    $tl_esc = sql_escape_string($tl_table);
    sql_query("
        DELETE FROM {$g5['timeline_temp_table']}
        WHERE tl_table = '{$tl_esc}'
          AND parent_wr_id = '{$parent_wr_id}'
          AND tu_id = '{$tu_id}'
    ");
    return true;
}

/**
 * 타임라인 사용자 목록 조회
 * @param string $tl_table 타임라인 테이블명
 * @return array
 */
function get_timeline_users($tl_table) {
    global $g5;
    $tl_table = sql_escape_string($tl_table);
    
    $result = sql_query("
        SELECT tu.*, c.ch_name, c.ch_side, c.ch_class, c.ch_state, m.mb_nick 
        FROM {$g5['timeline_user_table']} tu
        LEFT JOIN {$g5['character_table']} c ON c.ch_id = tu.ch_id
        LEFT JOIN {$g5['member_table']} m ON m.mb_id = c.mb_id
        WHERE tu.tl_table = '{$tl_table}'
        ORDER BY tu.tl_user_datetime DESC
    ");
    
    $list = array();
    while ($row = sql_fetch_array($result)) {
        $list[] = $row;
    }
    return $list;
}

/**
 * 타임라인 사용자인지 확인
 * @param string $tl_table 타임라인 테이블명
 * @param int $ch_id 캐릭터 ID
 * @return bool
 */
function is_timeline_user($tl_table, $ch_id) {
    global $g5;
    $tl_table = sql_escape_string($tl_table);
    $ch_id = (int)$ch_id;
    
    if (!$ch_id) return false;
    
    $row = sql_fetch("
        SELECT COUNT(*) AS cnt 
        FROM {$g5['timeline_user_table']} 
        WHERE tl_table = '{$tl_table}' AND ch_id = '{$ch_id}'
    ");
    
    return ($row['cnt'] > 0);
}

/**
 * 내 mb_id로 등록된 타임라인 사용자 목록
 * 정렬: ch_type main → sub → npc, 동률이면 ch_id 오름차순
 *
 * @param string $tl_table
 * @param string $mb_id
 * @return array
 */
function get_my_timeline_users($tl_table, $mb_id) {
    global $g5;

    static $cache = array();
    $tl_table = (string)$tl_table;
    $mb_id = (string)$mb_id;
    $cache_key = $tl_table.'|'.$mb_id;
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    $cache[$cache_key] = array();
    if ($tl_table === '' || $mb_id === '') {
        return $cache[$cache_key];
    }


    $tl_esc = sql_escape_string($tl_table);
    $mb_esc = sql_escape_string($mb_id);
    $result = sql_query("
        SELECT tu.*, c.ch_name, c.ch_type, c.ch_state, c.ch_side, c.ch_class
        FROM {$g5['timeline_user_table']} tu
        INNER JOIN {$g5['character_table']} c ON c.ch_id = tu.ch_id
        WHERE tu.tl_table = '{$tl_esc}'
          AND c.mb_id = '{$mb_esc}'
          AND c.ch_state != '삭제'
        ORDER BY CASE c.ch_type
            WHEN 'main' THEN 1
            WHEN 'sub' THEN 2
            WHEN 'npc' THEN 3
            ELSE 4
        END ASC, c.ch_id ASC
    ");

    $list = array();
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $list[] = $row;
        }
    }
    $cache[$cache_key] = $list;
    return $list;
}

/**
 * 해당 ch_id가 내 타임라인 사용자인지 확인
 */
function is_my_timeline_user($tl_table, $ch_id, $mb_id) {
    $ch_id = (int)$ch_id;
    if ($ch_id <= 0) {
        return false;
    }
    foreach (get_my_timeline_users($tl_table, $mb_id) as $user) {
        if ((int)ses($user, 'ch_id', 0, 'int') === $ch_id) {
            return true;
        }
    }
    return false;
}

function get_timeline_session_ch_id($tl_table) {
    $tl_table = (string)$tl_table;
    if ($tl_table === '' || !isset($_SESSION['tl_active_ch']) || !is_array($_SESSION['tl_active_ch'])) {
        return 0;
    }
    return isset($_SESSION['tl_active_ch'][$tl_table]) ? (int)$_SESSION['tl_active_ch'][$tl_table] : 0;
}

function set_timeline_session_ch_id($tl_table, $ch_id) {
    $tl_table = (string)$tl_table;
    if ($tl_table === '') {
        return false;
    }
    if (!isset($_SESSION['tl_active_ch']) || !is_array($_SESSION['tl_active_ch'])) {
        $_SESSION['tl_active_ch'] = array();
    }
    $_SESSION['tl_active_ch'][$tl_table] = (int)$ch_id;
    return true;
}

/**
 * 타임라인 활성 캐릭터 결정
 * 1) 세션에 저장된 ch_id가 내 사용자이면 그것
 * 2) 대표 캐릭터($member_ch_id)가 내 사용자이면 그것
 * 3) 아니면 ch_type main → sub → npc
 */
function resolve_timeline_active_ch_id($tl_table, $member_ch_id, $mb_id) {
    $member_ch_id = (int)$member_ch_id;
    $users = get_my_timeline_users($tl_table, $mb_id);
    if (empty($users)) {
        return $member_ch_id;
    }

    $ids = array();
    foreach ($users as $user) {
        $ids[(int)ses($user, 'ch_id', 0, 'int')] = true;
    }

    $session_ch = get_timeline_session_ch_id($tl_table);
    if ($session_ch > 0 && isset($ids[$session_ch])) {
        return $session_ch;
    }
    if ($member_ch_id > 0 && isset($ids[$member_ch_id])) {
        return $member_ch_id;
    }
    return (int)ses($users[0], 'ch_id', 0, 'int');
}

/**
 * 타임라인 요청에서 활성 캐릭터를 $character에 적용 (대표 캐릭터 member.ch_id는 유지)
 * @return array 내 타임라인 사용자 목록
 */
function apply_timeline_active_character($tl_table) {
    global $character, $member, $is_member;

    if (empty($is_member)) {
        return array();
    }
    $mb_id = ses($member, 'mb_id', '');
    if ($mb_id === '') {
        return array();
    }

    $users = get_my_timeline_users($tl_table, $mb_id);
    $current_ch = ses($character, 'ch_id', 0, 'int');
    $active_ch = resolve_timeline_active_ch_id($tl_table, $current_ch, $mb_id);

    if ($active_ch > 0 && $active_ch !== $current_ch && function_exists('get_character')) {
        $switched = get_character($active_ch);
        if (is_array($switched) && !empty($switched['ch_id'])) {
            $character = $switched;
            $GLOBALS['character'] = $character;
        }
    }

    return $users;
}

/**
 * 캐릭터명에서 기본 멘션 ID (띄어쓰기 앞부분, 최대 6자)
 */
function timeline_default_men_id_from_name($ch_name) {
    $name = trim((string)$ch_name);
    if ($name === '') {
        return '';
    }
    $parts = preg_split('/\s+/u', $name, 2);
    $first = (is_array($parts) && isset($parts[0])) ? $parts[0] : $name;
    if (mb_strlen($first) > 6) {
        $first = mb_substr($first, 0, 6);
    }
    return $first;
}

/**
 * 타임라인 사용자 추가
 * 프로필 기본값: tu_name = ch_name, tu_men_id = 이름 첫 토큰(최대 6자),
 * tu_thumb = 캐릭터 썸네일, tu_profile = /member/viewer.php?ch_id={ch_id}, tu_header/tu_bio = 빈칸
 * @param string $tl_table 타임라인 테이블명
 * @param int $ch_id 캐릭터 ID
 * @return bool
 */
function add_timeline_user($tl_table, $ch_id) {
    global $g5;
    $tl_table = sql_escape_string($tl_table);
    $ch_id = (int)$ch_id;
    
    if (!$ch_id) return false;

    
    // 중복 체크
    if (is_timeline_user($tl_table, $ch_id)) {
        return true;
    }

    $ch = sql_fetch("SELECT mb_id, ch_name FROM {$g5['character_table']} WHERE ch_id = '{$ch_id}'");
    if (!$ch) return false;

    $mb_id = sql_escape_string(ses($ch, 'mb_id', ''));
    $ch_name_raw = ses($ch, 'ch_name', '');
    $ch_name = sql_escape_string($ch_name_raw);
    $tu_men_id = sql_escape_string(timeline_default_men_id_from_name($ch_name_raw));

    $tu_thumb = '';
    if (function_exists('get_character_head')) {
        $tu_thumb = (string)get_character_head($ch_id);
    }
    $tu_thumb = sql_escape_string($tu_thumb);
    $tu_profile = sql_escape_string('/member/viewer.php?ch_id='.$ch_id);
    
    return sql_query("
        INSERT INTO {$g5['timeline_user_table']} 
        SET tl_table = '{$tl_table}',
            mb_id = '{$mb_id}',
            ch_id = '{$ch_id}',
            tu_men_id = '{$tu_men_id}',
            tu_name = '{$ch_name}',
            tu_thumb = '{$tu_thumb}',
            tu_header = '',
            tu_bio = '',
            tu_profile = '{$tu_profile}',
            tl_render_mode = 'normal',
            tl_user_datetime = '".G5_TIME_YMDHIS."'
    ");
}

/**
 * 타임라인 사용자 렌더 모드 (thread/normal)
 * 임시: 스레드 보기 비활성화 — 항상 normal
 */
function get_timeline_user_render_mode($tl_table, $ch_id) {
    return 'normal';
    /*
    global $g5;
    $tl_table = sql_escape_string((string)$tl_table);
    $ch_id = (int)$ch_id;
    if ($ch_id <= 0 || $tl_table === '') {
        return 'normal';
    }

    $row = sql_fetch("
        SELECT tl_render_mode
        FROM {$g5['timeline_user_table']}
        WHERE tl_table = '{$tl_table}' AND ch_id = '{$ch_id}'
    ", false);
    $mode = is_array($row) ? ses($row, 'tl_render_mode', 'normal', 'raw') : 'normal';
    if (!in_array($mode, array('thread', 'normal'), true)) {
        $mode = 'normal';
    }
    return $mode;
    */
}

/**
 * 타임라인 사용자 삭제
 * @param string $tl_table 타임라인 테이블명
 * @param int $ch_id 캐릭터 ID
 * @return bool
 */
function remove_timeline_user($tl_table, $ch_id) {
    global $g5;
    $tl_table = sql_escape_string($tl_table);
    $ch_id = (int)$ch_id;
    
    return sql_query("
        DELETE FROM {$g5['timeline_user_table']} 
        WHERE tl_table = '{$tl_table}' AND ch_id = '{$ch_id}'
    ");
}

/**
 * 타임라인 사용자 수 조회
 * @param string $tl_table 타임라인 테이블명
 * @return int
 */
function count_timeline_users($tl_table) {
    global $g5;
    $tl_table = sql_escape_string($tl_table);
    
    $row = sql_fetch("
        SELECT COUNT(*) AS cnt 
        FROM {$g5['timeline_user_table']} 
        WHERE tl_table = '{$tl_table}'
    ");
    
    return (int)$row['cnt'];
}

/**
 * 타임라인 글 작성 권한 확인
 * tl_write_level: 1=모든 사용자, 2=운영자만
 * 사용자가 등록되어 있으면 등록된 사용자 기반, 없으면 회원 레벨 기반
 * 
 * @param array $timeline 타임라인 설정
 * @param int $ch_id 캐릭터 ID
 * @param string $is_admin 관리자 여부
 * @return bool
 */
function can_write_timeline($timeline, $ch_id, $is_admin = '') {
    global $g5, $member;
    
    // 관리자는 항상 쓰기 가능
    if ($is_admin) {
        return true;
    }
    
    $tl_table = ses($timeline, 'tl_table', '');
    if (!$tl_table) return false;
    
    // 사용자 수 확인 (0이면 제한 없음)
    $user_count = count_timeline_users($tl_table);
    
    if ($user_count == 0) {
        // 사용자 제한 없음 - 모든 회원 쓰기 가능
        return true;
    }
    
    // 사용자 제한 있음 - 이 ch_id 또는 같은 mb_id의 다른 등록 캐릭터
    if (!is_timeline_user($tl_table, $ch_id)) {
        $mb_id = ses($member, 'mb_id', '');
        if (!$mb_id || empty(get_my_timeline_users($tl_table, $mb_id))) {
            return false;
        }
    }
    
    // 쓰기 레벨 확인: 1=모든 사용자, 2=운영자만
    $tl_write_level = ses($timeline, 'tl_write_level', 1, 'int');
    
    if ($tl_write_level == 2) {
        // 운영자만 쓰기 가능
        return ($is_admin);
    }
    
    // 모든 사용자 쓰기 가능
    return true;
}

/**
 * 타임라인 DM 사용 가능 여부
 * tl_use_dm=0 이면 불가. 관리자는 항상(기능 on일 때). 등록 사용자 0명이면 회원 캐릭터. 그 외 이 타임라인 등록 사용자.
 */
function can_use_timeline_dm($tl_table, $ch_id, $is_admin = '') {
    global $timeline;

    if (!timeline_dm_enabled($timeline, $tl_table)) {
        return false;
    }

    $ch_id = (int)$ch_id;
    if ($is_admin) {
        return true;
    }
    if ($ch_id <= 0 || (string)$tl_table === '') {
        return false;
    }
    if (count_timeline_users($tl_table) == 0) {
        return true;
    }
    return is_timeline_user($tl_table, $ch_id);
}

/**
 * 타임라인 설정에서 DM 기능 on 여부
 */
function timeline_dm_enabled($timeline, $tl_table = '') {
    $use = 1;
    if (is_array($timeline) && ($tl_table === '' || ses($timeline, 'tl_table', '') === (string)$tl_table)) {
        $use = ses($timeline, 'tl_use_dm', 1, 'int');
    } elseif ($tl_table !== '') {
        $row = get_timeline($tl_table);
        $use = is_array($row) ? ses($row, 'tl_use_dm', 1, 'int') : 1;
    }
    return ($use == 1);
}

/**
 * 폴링 간격(ms). 최소 1000, 최대 300000. 없거나 너무 작으면 $default.
 */
function get_timeline_poll_interval_ms($timeline, $key = 'tl_poll_interval', $default = 10000) {
    $ms = is_array($timeline) ? ses($timeline, $key, $default, 'int') : $default;
    if ($ms < 1000) $ms = $default;
    if ($ms > 300000) $ms = 300000;
    return $ms;
}

/**
 * DM 초대/참가 대상이 이 타임라인 사용자인지
 */
function is_valid_dm_participant($tl_table, $ch_id) {
    global $g5;
    $ch_id = (int)$ch_id;
    if ($ch_id <= 0 || (string)$tl_table === '') {
        return false;
    }
    if (count_timeline_users($tl_table) > 0) {
        return is_timeline_user($tl_table, $ch_id);
    }
    $ch = sql_fetch("SELECT ch_id FROM {$g5['character_table']} WHERE ch_id = '{$ch_id}' AND ch_state != '삭제'");
    return (is_array($ch) && !empty($ch['ch_id']));
}

/**
 * 타임라인 열람 권한 확인
 * tl_read_level: 1=전체, 2=회원만, 3=사용자만, 10=운영자만
 * 
 * @param array $timeline 타임라인 설정
 * @param int $ch_id 캐릭터 ID
 * @param string $is_admin 관리자 여부
 * @param bool $is_member 회원 여부
 * @return bool
 */
function can_view_timeline($timeline, $ch_id, $is_admin = '', $is_member = false) {
    global $g5, $member;
    
    // 관리자는 항상 열람 가능
    if ($is_admin) {
        return true;
    }
    
    $tl_table = ses($timeline, 'tl_table', '');
    $tl_read_level = ses($timeline, 'tl_read_level', 1, 'int');
    
    switch ($tl_read_level) {
        case 10: // 운영자만
            return ($is_admin);
            
        case 3: // 사용자만
            if (is_timeline_user($tl_table, $ch_id)) {
                return true;
            }
            $mb_id = is_array($member) ? ses($member, 'mb_id', '') : '';
            return ($mb_id !== '' && !empty(get_my_timeline_users($tl_table, $mb_id)));
            
        case 2: // 회원만
            return $is_member;
            
        case 1: // 전체 (비회원 포함)
        default:
            return true;
    }
}

/**
 * 열람 권한 레벨 라벨
 * @param int $level
 * @return string
 */
function get_timeline_read_level_label($level) {
    $labels = array(
        1 => '전체',
        2 => '회원',
        3 => '사용자',
        10 => '운영자'
    );
    return isset($labels[$level]) ? $labels[$level] : $labels[1];
}

/**
 * 쓰기 권한 레벨 라벨
 * @param int $level
 * @return string
 */
function get_timeline_write_level_label($level) {
    $labels = array(
        1 => '모든 사용자',
        2 => '운영자만'
    );
    return isset($labels[$level]) ? $labels[$level] : $labels[1];
}


// =====================================================
// 글 작성/수정/삭제 함수
// =====================================================

/**
 * 타임라인 글 작성
 * @param string $tl_table 타임라인 테이블명
 * @param int $ch_id 작성자 캐릭터 ID
 * @param string $content 글 내용
 * @param array $options 추가 옵션 (parent_id, wr_image1~4, wr_file, log, item_log, dice1, dice2, wr_1~10)
 * @return array ['success' => bool, 'message' => string, 'wr_id' => int]
 */
function insert_timeline_post($tl_table, $ch_id, $content, $options = array()) {
    global $g5, $member;
    
    $write_table = get_timeline_write_table($tl_table);
    $ch_id = (int)$ch_id;
    // G5는 common.php 에서 $_POST 전체에 addslashes 를 이미 적용함.
    // 기믹/멘션 처리는 원문으로 하고, SQL 직전에 1회만 이스케이프한다.
    $content = trim(stripslashes((string)$content));
    $mb_id = sql_escape_string(ses($member, 'mb_id', ''));
    $time = G5_TIME_YMDHIS;
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // 옵션 처리
    // parent_id: 원글 ID (댓글을 달 대상 원글)
    // reply_to: 답장 대상 댓글 ID (대댓글 시, 같은 타래 유지용)
    $parent_id = ses($options, 'parent_id', 0, 'int');
    $reply_to_id = ses($options, 'reply_to', 0, 'int');
    
    $wr_log = sql_escape_string(ses($options, 'log', '', 'raw'));
    $wr_item_log = sql_escape_string(ses($options, 'item_log', '', 'raw'));
    $dice1 = ses($options, 'dice1', 0, 'int');
    $dice2 = ses($options, 'dice2', 0, 'int');
    
    // 복수 이미지 처리
    $wr_image1 = sql_escape_string(ses($options, 'wr_image1', '', 'raw'));
    $wr_image2 = sql_escape_string(ses($options, 'wr_image2', '', 'raw'));
    $wr_image3 = sql_escape_string(ses($options, 'wr_image3', '', 'raw'));
    $wr_image4 = sql_escape_string(ses($options, 'wr_image4', '', 'raw'));
    $wr_file = ses($options, 'wr_file', 0, 'int');
    
    $result = array('success' => false, 'message' => '', 'wr_id' => 0);
    
    if (!$ch_id) {
        $result['message'] = '캐릭터 정보가 필요합니다.';
        return $result;
    }
    
    $customer_sql = ses($options, 'customer_sql', '', 'raw');
    if (empty($content) && empty($wr_log) && empty($wr_item_log) && !$wr_file && $customer_sql === '') {
        $result['message'] = '내용을 입력해주세요.';
        return $result;
    }
    
    // 작성자명: timeline_user.tu_name 스냅샷 (없으면 캐릭터명)
    // 타이틀: 캐릭터 착용 타이틀(ch_title) 스냅샷 — MMB write_update 의 ti_id 와 동일
    $GLOBALS['tl_table'] = $tl_table;
    $tl_safe = sql_escape_string($tl_table);
    $tu_row = sql_fetch("
        SELECT tu_name FROM {$g5['timeline_user_table']}
        WHERE tl_table = '{$tl_safe}' AND ch_id = '{$ch_id}'
    ", false);
    $wr_name = (is_array($tu_row)) ? ses($tu_row, 'tu_name', '', 'raw') : '';
    $character_info = sql_fetch("SELECT ch_name, ch_title FROM {$g5['character_table']} WHERE ch_id = '{$ch_id}'");
    if ($wr_name === '') {
        $wr_name = is_array($character_info) ? ses($character_info, 'ch_name', '', 'raw') : '';
    }
    $wr_name = sql_escape_string($wr_name);
    $ti_id = is_array($character_info) ? (int)ses($character_info, 'ch_title', 0, 'int') : 0;
    if ($ti_id < 0) {
        $ti_id = 0;
    }
    
    // 변수 초기화
    $is_comment = 0;
    $wr_parent = 0;
    $wr_num = 0;
    $wr_mention_root = 0;
    $wr_reply_to = 0;
    $origin_id = 0;
    $reply_to_ch_id = 0;
    $reply_to_men_id = '';  // 답글 대상 멘션 ID (tu_men_id)
    
    if ($parent_id > 0) {
        // 댓글인 경우 - 원글 정보 조회
        $origin = sql_fetch("SELECT wr_id, wr_num, wr_parent, ch_id, wr_name FROM {$write_table} WHERE wr_id = '{$parent_id}'");
        if (!$origin || empty($origin['wr_id'])) {
            $result['message'] = '원글을 찾을 수 없습니다.';
            return $result;
        }
        
        $is_comment = 1;
        $origin_id = $parent_id;
        $wr_parent = $parent_id;
        $wr_num = $origin['wr_num'];
        
        // reply_to가 있으면 대댓글 (같은 타래 유지)
        if ($reply_to_id > 0) {
            $reply_to = sql_fetch("SELECT wr_id, wr_mention_root, ch_id, wr_name 
                                   FROM {$write_table}
                                   WHERE wr_id = '{$reply_to_id}'");
            if ($reply_to['wr_id']) {
                $wr_reply_to = $reply_to_id;
                $reply_to_ch_id = (int)$reply_to['ch_id'];
                // 같은 멘션 타래로 유지
                $wr_mention_root = (int)$reply_to['wr_mention_root'];
            } else {
                // reply_to가 유효하지 않으면 원글에 대한 직접 댓글로 처리
                $wr_reply_to = $parent_id;  // 원글 ID
                $max_root = sql_fetch("SELECT MAX(wr_mention_root) AS max_root FROM {$write_table} WHERE wr_parent = '{$origin_id}'");
                $wr_mention_root = (int)$max_root['max_root'] + 1;
                $reply_to_ch_id = (int)$origin['ch_id'];
            }
        } else {
            // 1차 댓글 - 원글에 대한 직접 답글
            $wr_reply_to = $parent_id;  // 원글 ID를 wr_reply_to에 저장
            $max_root = sql_fetch("SELECT MAX(wr_mention_root) AS max_root FROM {$write_table} WHERE wr_parent = '{$origin_id}'");
            $wr_mention_root = (int)$max_root['max_root'] + 1;
            $reply_to_ch_id = (int)$origin['ch_id'];
        }

        $reply_to_men_id = get_timeline_user_men_id($tl_table, $reply_to_ch_id);
        
        // 답글 대상 멘션 ID가 있고, 콘텐츠가 이미 @멘션으로 시작하지 않으면 자동 추가
        if ($reply_to_men_id && !preg_match('/^@["\']?' . preg_quote($reply_to_men_id, '/') . '/', $content)) {
            if (strpos($reply_to_men_id, ' ') !== false) {
                $content = '@"' . $reply_to_men_id . '" ' . $content;
            } else {
                $content = '@' . $reply_to_men_id . ' ' . $content;
            }
        }
    } else {
        // 새 원글인 경우
        $max_num = sql_fetch("SELECT MIN(wr_num) AS min_num FROM {$write_table}");
        $wr_num = (int)$max_num['min_num'] - 1;
        // wr_parent는 INSERT 후 -wr_id로 설정
    }

    // 작성 시 기믹: [NdM] 주사위 / [A/B/C] 랜덤 선택 볼드 (수정 시에는 재처리하지 않음)
    // 스크립트(HTML) 글은 기믹·멘션 처리 생략
    $is_script = (!empty($options['is_script']) || !empty($options['wr_script'])) ? 1 : 0;
    $mentions = array();
    if (!$is_script) {
        $content = timeline_process_content_gimmicks($content);
        $mentions = parse_timeline_mentions($content, $tl_table);
    }
    
    // 콘텐츠 이스케이프 (멘션 파싱 후)
    $content = sql_escape_string($content);
    
    // wr_1 ~ wr_10 처리
    $wr_extra = '';
    for ($i = 1; $i <= 10; $i++) {
        $val = sql_escape_string(ses($options, 'wr_'.$i, '', 'raw'));
        $wr_extra .= ", wr_{$i} = '{$val}'";
    }
    
    // 작성 시점 썸네일 스냅샷 (timeline_user.tu_thumb → get_timeline_thumb)
    // get_timeline_thumb은 global $tl_table을 참조하므로 저장 대상 테이블을 맞춤
    $GLOBALS['tl_table'] = $tl_table;
    $wr_thumb = sql_escape_string(get_timeline_post_thumb_url($ch_id));

    // 이미지 접기 / 글 가리기
    $wr_img_hidden = ses($options, 'wr_img_hidden', 0, 'int') ? 1 : 0;
    $wr_text_hidden = ses($options, 'wr_text_hidden', 0, 'int') ? 1 : 0;
    $wr_waring_text = sql_escape_string(ses($options, 'wr_waring_text', '', 'string'));
    if (!$wr_text_hidden) {
        $wr_waring_text = '';
    }
    
    // 글 등록
    $has_wr_script = function_exists('table_has_column')
        ? table_has_column($write_table, 'wr_script')
        : true;
    $wr_script_sql = $has_wr_script ? ", wr_script = '{$is_script}'" : '';

    sql_query("
        INSERT INTO {$write_table} SET
            wr_num = '{$wr_num}',
            wr_parent = '{$wr_parent}',
            wr_is_comment = '{$is_comment}',
            wr_mention_root = '{$wr_mention_root}',
            wr_reply_to = '{$wr_reply_to}',
            ch_id = '{$ch_id}',
            mb_id = '{$mb_id}',
            wr_name = '{$wr_name}',
            wr_content = '{$content}',
            wr_ip = '{$ip}',
            wr_datetime = '{$time}',
            wr_last = '{$time}',
            wr_log = '{$wr_log}',
            wr_item_log = '{$wr_item_log}',
            wr_dice1 = '{$dice1}',
            wr_dice2 = '{$dice2}',
            wr_bump_datetime = '{$time}',
            wr_file = '{$wr_file}',
            wr_image1 = '{$wr_image1}',
            wr_image2 = '{$wr_image2}',
            wr_image3 = '{$wr_image3}',
            wr_image4 = '{$wr_image4}',
            wr_img_hidden = '{$wr_img_hidden}',
            wr_text_hidden = '{$wr_text_hidden}',
            wr_waring_text = '{$wr_waring_text}',
            wr_thumb = '{$wr_thumb}',
            ti_id = '{$ti_id}'
            {$wr_script_sql}
            {$wr_extra}
    ");
    
    $wr_id = sql_insert_id();
    
    if (!$wr_id) {
        $result['message'] = '글 등록에 실패했습니다.';
        return $result;
    }

    // 스크립트 플래그 재확인 (컬럼 추가 직후/누락 대비)
    if ($is_script) {
        if (function_exists('table_has_column') && table_has_column($write_table, 'wr_script')) {
            sql_query("UPDATE {$write_table} SET wr_script = 1 WHERE wr_id = '{$wr_id}'");
        }
    }
    
    // customer_sql 처리 (스킨에서 설정된 추가 SQL)
    if ($customer_sql) {
        sql_query("UPDATE {$write_table} SET wr_id = '{$wr_id}' {$customer_sql} WHERE wr_id = '{$wr_id}'");
    }
    
    // 원글인 경우 wr_parent = -wr_id로 설정
    if ($parent_id == 0) {
        sql_query("UPDATE {$write_table} SET wr_parent = -{$wr_id} WHERE wr_id = '{$wr_id}'");
    }
    
    // 댓글인 경우 업데이트 및 멘션 등록
    if ($origin_id > 0) {
        // 원글의 wr_last 업데이트 (최신 활동 시간)
        sql_query("
            UPDATE {$write_table} 
            SET wr_reply_count = wr_reply_count + 1,
                wr_last = '{$time}'
            WHERE wr_id = '{$origin_id}'
        ");
        
        // 직전 글(wr_reply_to)의 wr_comment 증가
        // wr_reply_to가 origin_id이면 원글에 직접 달린 댓글
        $reply_target_id = ($wr_reply_to > 0) ? $wr_reply_to : $origin_id;
        sql_query("
            UPDATE {$write_table} 
            SET wr_comment = wr_comment + 1
            WHERE wr_id = '{$reply_target_id}'
        ");
        
        // 답장 대상자에게 멘션 등록 (본인 제외)
        if ($reply_to_ch_id && $reply_to_ch_id != $ch_id) {
            insert_timeline_mention($tl_table, $wr_id, $reply_to_ch_id);
        }
    }
    
    // 멘션 등록 (본인 멘션은 insert_timeline_mention에서 제외)
    foreach ($mentions as $mentioned_ch_id) {
        insert_timeline_mention($tl_table, $wr_id, $mentioned_ch_id);
    }
    
    // 타임라인 글 수 증가
    sql_query("UPDATE {$g5['timeline_table']} SET tl_count_write = tl_count_write + 1 WHERE tl_table = '{$tl_table}'");
    
    $result['success'] = true;
    $result['message'] = '등록되었습니다.';
    $result['wr_id'] = $wr_id;
    $result['mention_root'] = $wr_mention_root;
    $result['is_comment'] = $is_comment;
    
    return $result;
}

/**
 * 타임라인 글/댓글 수정 권한
 * 작성 캐릭터 본인 또는 관리자 (`$is_admin` 이 참)
 */
function can_edit_timeline_post($post, $ch_id, $is_admin = '') {
    if (!is_array($post) || empty($post['wr_id'])) return false;
    if ($is_admin) return true;
    return ((int)ses($post, 'ch_id', 0, 'int') === (int)$ch_id);
}

/**
 * 타임라인 글/댓글 수정
 * 허용: wr_content, 이미지, wr_img_hidden, wr_text_hidden, wr_waring_text
 * 금지: wr_thumb, wr_name, wr_log, wr_item_log, wr_dice*, ch_id, mb_id, ti_id 등
 *
 * @param array $options keep_images(array), new_images(array filenames), wr_img_hidden, wr_text_hidden, wr_waring_text, content
 */
function update_timeline_post($tl_table, $wr_id, $ch_id, $options = array(), $is_admin = '') {
    global $g5, $timeline;

    $write_table = get_timeline_write_table($tl_table);
    $wr_id = (int)$wr_id;
    $ch_id = (int)$ch_id;
    $result = array('success' => false, 'message' => '', 'wr_id' => $wr_id);

    $post = sql_fetch("SELECT * FROM {$write_table} WHERE wr_id = '{$wr_id}' AND wr_deleted = 0");
    if (!$post || empty($post['wr_id'])) {
        $result['message'] = '글을 찾을 수 없습니다.';
        return $result;
    }

    if (!can_edit_timeline_post($post, $ch_id, $is_admin)) {
        $result['message'] = '수정 권한이 없습니다.';
        return $result;
    }

    $is_script = (!empty($options['is_script']) || !empty($options['wr_script'])
        || (function_exists('timeline_row_is_script') && timeline_row_is_script($post))) ? 1 : 0;

    $content = trim(ses($options, 'content', '', 'raw'));
    // 스크립트는 호출측에서 stripslashes 한 원문을 넘김. 일반 AJAX 수정은 $_POST addslashes 상태.
    if (!$is_script) {
        $content = stripslashes($content);
    }
    $keep_images = ses($options, 'keep_images', array(), 'raw');
    if (!is_array($keep_images)) $keep_images = array();

    $new_images = ses($options, 'new_images', array(), 'raw');
    if (!is_array($new_images)) $new_images = array();

    $max_images = 4;
    if (is_array($timeline)) {
        $max_images = ses($timeline, 'tl_image_count', 4, 'int');
        if ($max_images < 1) $max_images = 4;
        if ($max_images > 4) $max_images = 4;
    }

    // 기존 파일명 화이트리스트
    $existing = array();
    for ($i = 1; $i <= 4; $i++) {
        $fn = ses($post, 'wr_image'.$i, '', 'raw');
        if ($fn !== '') $existing[$fn] = true;
    }

    $final_images = array();
    foreach ($keep_images as $fn) {
        $fn = basename((string)$fn);
        if ($fn === '' || empty($existing[$fn])) continue;
        if (!in_array($fn, $final_images, true)) $final_images[] = $fn;
        if (count($final_images) >= $max_images) break;
    }
    foreach ($new_images as $fn) {
        $fn = basename((string)$fn);
        if ($fn === '') continue;
        if (!in_array($fn, $final_images, true)) $final_images[] = $fn;
        if (count($final_images) >= $max_images) break;
    }

    if ($is_script) {
        $content_check = strtolower(preg_replace('/\s+/', '', $content));
        $empty_vals = array('', '&nbsp;', '<p>&nbsp;</p>', '<p><br></p>', '<div><br></div>', '<p></p>', '<br>');
        if ((in_array($content_check, $empty_vals, true) || trim(strip_tags($content)) === '')
            && !preg_match('/<img\b/i', $content)) {
            $result['message'] = '내용을 입력해주세요.';
            return $result;
        }
    } elseif ($content === '' && empty($final_images)) {
        $result['message'] = '내용을 입력해주세요.';
        return $result;
    }

    // 멘션은 일반 글만 재파싱. wr_thumb/wr_name 미변경.
    $mentions = array();
    if (!$is_script && function_exists('parse_timeline_mentions')) {
        $mentions = parse_timeline_mentions($content, $tl_table);
    }

    $content_esc = sql_escape_string($content);
    $wr_img_hidden = ses($options, 'wr_img_hidden', 0, 'int') ? 1 : 0;
    $wr_text_hidden = ses($options, 'wr_text_hidden', 0, 'int') ? 1 : 0;
    $wr_waring_text = sql_escape_string(ses($options, 'wr_waring_text', '', 'string'));
    if (!$wr_text_hidden) $wr_waring_text = '';

    $imgs = array('', '', '', '');
    for ($i = 0; $i < 4; $i++) {
        $imgs[$i] = isset($final_images[$i]) ? sql_escape_string($final_images[$i]) : '';
    }
    $wr_file = count($final_images);
    $time = G5_TIME_YMDHIS;

    // wr_thumb, wr_name, wr_log, wr_item_log, dice, ch_id, mb_id 등은 SET에 포함하지 않음
    sql_query("
        UPDATE {$write_table} SET
            wr_content = '{$content_esc}',
            wr_image1 = '{$imgs[0]}',
            wr_image2 = '{$imgs[1]}',
            wr_image3 = '{$imgs[2]}',
            wr_image4 = '{$imgs[3]}',
            wr_file = '{$wr_file}',
            wr_img_hidden = '{$wr_img_hidden}',
            wr_text_hidden = '{$wr_text_hidden}',
            wr_waring_text = '{$wr_waring_text}',
            wr_modified = 1,
            wr_last = '{$time}'
        WHERE wr_id = '{$wr_id}'
    ");

    // 멘션 재등록(기존 방식과 동일하게 추가). 실패해도 본문 수정은 유지.
    if (!empty($mentions) && function_exists('insert_timeline_mention')) {
        foreach ($mentions as $mentioned_ch_id) {
            insert_timeline_mention($tl_table, $wr_id, $mentioned_ch_id);
        }
    }

    $result['success'] = true;
    $result['message'] = '수정되었습니다.';
    $result['wr_id'] = $wr_id;
    $result['parent_id'] = (int)ses($post, 'wr_parent', 0, 'int');
    $result['is_comment'] = (int)ses($post, 'wr_is_comment', 0, 'int') ? 1 : 0;
    return $result;
}


/**
 * 타임라인 글 삭제
 * @param string $tl_table 타임라인 테이블명
 * @param int $wr_id 글 ID
 * @param int $ch_id 요청자 캐릭터 ID (본인 확인용)
 * @param bool $is_admin 관리자 여부
 * @return array ['success' => bool, 'message' => string]
 */
function delete_timeline_post($tl_table, $wr_id, $ch_id, $is_admin = false) {
    global $g5;
    
    $write_table = get_timeline_write_table($tl_table);
    $wr_id = (int)$wr_id;
    $ch_id = (int)$ch_id;
    
    $write = sql_fetch("SELECT * FROM {$write_table} WHERE wr_id = '{$wr_id}'");
    
    if (!$write || !isset($write['wr_id']) || !$write['wr_id']) {
        return array('success' => false, 'message' => '글을 찾을 수 없습니다.');
    }
    
    if (!$is_admin && $write['ch_id'] != $ch_id) {
        return array('success' => false, 'message' => '본인의 글만 삭제할 수 있습니다.');
    }
    
    // 첨부파일 삭제 (TODO: 파일 테이블 연동 시)
    
    // 원글인 경우 (wr_parent < 0 = 음수)
    if ($write['wr_parent'] < 0) {
        // 모든 댓글 삭제 (댓글의 wr_parent = 원글 wr_id)
        sql_query("DELETE FROM {$write_table} WHERE wr_parent = '{$wr_id}'");
        
        // 관련 좋아요/북마크/멘션 삭제
        sql_query("DELETE FROM {$g5['timeline_good_table']} WHERE tl_table = '{$tl_table}' AND wr_id IN (SELECT wr_id FROM {$write_table} WHERE wr_parent = '{$wr_id}')");
        sql_query("DELETE FROM {$g5['timeline_bookmark_table']} WHERE tl_table = '{$tl_table}' AND wr_id IN (SELECT wr_id FROM {$write_table} WHERE wr_parent = '{$wr_id}')");
        sql_query("DELETE FROM {$g5['timeline_mention_table']} WHERE tl_table = '{$tl_table}' AND wr_id IN (SELECT wr_id FROM {$write_table} WHERE wr_parent = '{$wr_id}')");
        
        // 삭제된 댓글 수만큼 감소
        $comment_count = (int)$write['wr_comment'];
        sql_query("UPDATE {$g5['timeline_table']} SET tl_count_write = tl_count_write - 1 - {$comment_count} WHERE tl_table = '{$tl_table}'");
    } else {
        // 댓글인 경우 (wr_parent > 0)
        // 원글의 wr_reply_count 감소
        sql_query("UPDATE {$write_table} SET wr_reply_count = wr_reply_count - 1 WHERE wr_id = '{$write['wr_parent']}'");
        
        // 직전 글(wr_reply_to)의 wr_comment 감소
        $reply_to = (int)ses($write, 'wr_reply_to', 0, 'int');
        $reply_target_id = ($reply_to > 0) ? $reply_to : $write['wr_parent'];
        sql_query("UPDATE {$write_table} SET wr_comment = wr_comment - 1 WHERE wr_id = '{$reply_target_id}'");
        
        sql_query("UPDATE {$g5['timeline_table']} SET tl_count_write = tl_count_write - 1 WHERE tl_table = '{$tl_table}'");
    }
    
    // 해당 글 관련 데이터 삭제
    sql_query("DELETE FROM {$g5['timeline_good_table']} WHERE tl_table = '{$tl_table}' AND wr_id = '{$wr_id}'");
    sql_query("DELETE FROM {$g5['timeline_bookmark_table']} WHERE tl_table = '{$tl_table}' AND wr_id = '{$wr_id}'");
    sql_query("DELETE FROM {$g5['timeline_mention_table']} WHERE tl_table = '{$tl_table}' AND wr_id = '{$wr_id}'");
    sql_query("DELETE FROM {$write_table} WHERE wr_id = '{$wr_id}'");
    
    return array('success' => true, 'message' => '삭제되었습니다.');
}


// =====================================================
// 글 목록 조회 함수
// =====================================================

/**
 * 타임라인 글 목록 조회
 * 
 * @param string $tl_table 타임라인 테이블명
 * @param string $type 타입 (all, public, mentions, profile, profile_public, profile_media, bookmarks)
 * @param int $ch_id 현재 캐릭터 ID
 * @param array $options 추가 옵션 (target_ch_id, last_id, limit, stx)
 * @return array 글 목록
 */
function get_timeline_posts($tl_table, $type, $ch_id, $options = array()) {
    global $g5;
    
    $write_table = get_timeline_write_table($tl_table);
    $ch_id = (int)$ch_id;
    $target_ch_id = ses($options, 'target_ch_id', 0, 'int');
    $last_sort_time = ses($options, 'last_sort_time', '', 'raw');
    $last_id = ses($options, 'last_id', 0, 'int');
    $limit = ses($options, 'limit', 20, 'int');
    $stx = ses($options, 'stx', '', 'raw');
    $render_mode = ses($options, 'render_mode', 'normal', 'raw');
    
    // 테이블 존재 확인
    $table_exists = sql_fetch("SHOW TABLES LIKE '{$write_table}'");
    if (!$table_exists) {
        return array();
    }
    
    // 기본 조건: 원글만 (wr_parent < 0)
    $where = "w.wr_parent < 0 AND w.wr_deleted = 0";
    
    switch ($type) {
        case 'public':
            // 공개 원글만 (답글/멘션 댓글 제외)
            $where = "w.wr_parent < 0 AND w.wr_is_comment = 0 AND w.wr_deleted = 0";
            break;
            
        case 'mentions':
            // 나를 멘션한 글/댓글 자체를 표시 (원글/댓글 구분 없이)
            $where = "w.wr_deleted = 0 AND w.wr_id IN (
                SELECT wr_id FROM {$g5['timeline_mention_table']} 
                WHERE tl_table = '{$tl_table}' AND ch_id = '{$ch_id}'
            )";
            break;
            
        case 'profile':
        case 'profile_replies':
            // 특정 캐릭터의 모든 글 (원글+답글, 최신순) — 스크립트 제외
            $where = "w.wr_deleted = 0 AND w.ch_id = '{$target_ch_id}'";
            if (function_exists('table_has_column') && table_has_column($write_table, 'wr_script')) {
                $where .= " AND IFNULL(w.wr_script,0) = 0";
            }
            break;
            
        case 'profile_public':
            // 특정 캐릭터의 원글만 (답글/멘션 댓글 제외) — 스크립트 제외
            $where = "w.wr_deleted = 0 AND w.ch_id = '{$target_ch_id}' AND w.wr_is_comment = 0 AND w.wr_parent < 0";
            if (function_exists('table_has_column') && table_has_column($write_table, 'wr_script')) {
                $where .= " AND IFNULL(w.wr_script,0) = 0";
            }
            break;

        case 'profile_media':
            // 특정 캐릭터의 이미지 첨부 글 (원글+답글, 퍼블/멘션 구분 없음) — 스크립트 제외
            $where = "w.wr_deleted = 0 AND w.ch_id = '{$target_ch_id}' AND w.wr_file > 0";
            if (function_exists('table_has_column') && table_has_column($write_table, 'wr_script')) {
                $where .= " AND IFNULL(w.wr_script,0) = 0";
            }
            break;
            
        case 'bookmarks':
            // 북마크한 글
            $where = "w.wr_deleted = 0 AND w.wr_id IN (
                SELECT wr_id FROM {$g5['timeline_bookmark_table']} 
                WHERE tl_table = '{$tl_table}' AND ch_id = '{$ch_id}'
            )";
            break;
            
        case 'all':
        default:
            if ($render_mode === 'normal') {
                // normal 모드: 삭제되지 않은 모든 글 (원글/댓글 구분 없이)
                $where = "w.wr_deleted = 0";
            } else {
                // thread 모드: 내가 쓴 글 OR 나를 멘션한 글 OR 퍼블릭 원글
                $where = "w.wr_deleted = 0 AND (
                    w.ch_id = '{$ch_id}'
                    OR w.wr_parent < 0
                    OR w.wr_id IN (
                        SELECT wr_id FROM {$g5['timeline_mention_table']} 
                        WHERE tl_table = '{$tl_table}' AND ch_id = '{$ch_id}'
                    )
                )";
            }
            break;
    }
    
    // 검색어
    if ($stx) {
        $stx_safe = sql_escape_string($stx);
        $where .= " AND (w.wr_content LIKE '%{$stx_safe}%' OR w.wr_name LIKE '%{$stx_safe}%')";
    }
    
  
    if ($last_id > 0) {
        $last_post = sql_fetch("SELECT wr_datetime FROM {$write_table} WHERE wr_id = '{$last_id}'");
        if ($last_post['wr_datetime']) {
            $where .= " AND (
                w.wr_datetime < '{$last_post['wr_datetime']}'
                OR (w.wr_datetime = '{$last_post['wr_datetime']}' AND w.wr_id < '{$last_id}')
            )";
        }
    }
    
    // 멘션 탭: 현재 캐릭터(tu_id=ch_id)가 이 글에 단 직접 답글 수. mb_id(다른 캐릭터)는 세지 않음.
    $my_reply_sql = '';
    if ($type === 'mentions') {
        $my_reply_sql = ",
                (SELECT COUNT(*) FROM {$write_table} r
                  WHERE r.wr_deleted = 0
                    AND r.wr_reply_to = w.wr_id
                    AND r.ch_id = '{$ch_id}') AS my_reply";
    }

    $sql = "
        SELECT w.*, 
                c.ch_name, c.ch_thumb,
                w.wr_datetime AS sort_time,
                (SELECT COUNT(*) FROM {$g5['timeline_good_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$ch_id}') AS my_like,
                (SELECT COUNT(*) FROM {$g5['timeline_bookmark_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$ch_id}') AS my_bookmark
                {$my_reply_sql}
        FROM {$write_table} w
        LEFT JOIN {$g5['character_table']} c ON c.ch_id = w.ch_id
        WHERE {$where}
        ORDER BY w.wr_datetime DESC, w.wr_id DESC
        LIMIT {$limit}
    ";

    
    $result = sql_query($sql);
    $list = array();
    
    while ($row = sql_fetch_array($result)) {
        $list[] = $row;
    }
    
    return $list;
}


/**
 * 특정 원글에서 내 댓글/나를 호출한 댓글의 멘션 타래 목록 조회
 * 각 멘션 타래(wr_mention_root)별로 최신 댓글 1개씩 반환
 * 
 * @param string $write_table 글 테이블명
 * @param string $tl_table 타임라인 테이블명
 * @param int $origin_id 원글 ID
 * @param int $ch_id 내 캐릭터 ID
 * @return array 멘션 타래 배열
 */
function get_my_mention_threads($write_table, $tl_table, $origin_id, $ch_id) {
    global $g5;
    
    $origin_id = (int)$origin_id;
    $ch_id = (int)$ch_id;
    
    // 내 댓글 또는 나를 멘션한 댓글의 wr_mention_root 목록
    $threads = array();
    
    $sql = "
        SELECT DISTINCT c.wr_mention_root
        FROM {$write_table} c
        LEFT JOIN {$g5['timeline_mention_table']} m 
            ON m.tl_table = '{$tl_table}' AND m.wr_id = c.wr_id AND m.ch_id = '{$ch_id}'
        WHERE c.wr_parent = '{$origin_id}'
        AND c.wr_deleted = 0
        AND (c.ch_id = '{$ch_id}' OR m.tl_mt_id IS NOT NULL)
        ORDER BY c.wr_mention_root ASC
    ";
    
    $result = sql_query($sql);
    $roots = array();
    while ($row = sql_fetch_array($result)) {
        $roots[] = (int)$row['wr_mention_root'];
    }
    
    if (empty($roots)) {
        return array();
    }
    
    // 각 멘션 타래별 최신 댓글 조회
    foreach ($roots as $root) {
        $thread_sql = "
            SELECT c.*, ch.ch_name, ch.ch_thumb
            FROM {$write_table} c
            LEFT JOIN {$g5['character_table']} ch ON ch.ch_id = c.ch_id
            WHERE c.wr_parent = '{$origin_id}'
            AND c.wr_mention_root = '{$root}'
            AND c.wr_deleted = 0
            ORDER BY c.wr_datetime DESC
            LIMIT 1
        ";
        $thread = sql_fetch($thread_sql);
        if ($thread['wr_id']) {
            // 해당 타래의 전체 댓글 수
            $count = sql_fetch("SELECT COUNT(*) AS cnt FROM {$write_table} WHERE wr_parent = '{$origin_id}' AND wr_mention_root = '{$root}' AND wr_deleted = 0");
            $thread['thread_count'] = (int)$count['cnt'];
            $thread['wr_mention_root'] = $root;
            $threads[] = $thread;
        }
    }
    
    return $threads;
}


/**
 * 단일 글 상세 조회 (답글 포함)
 * 원글: wr_parent < 0 (음수 = -wr_id)
 * 댓글: wr_parent > 0 (양수 = 원글의 wr_id)
 * 
 * @param string $tl_table 타임라인 테이블명
 * @param int $wr_id 글 ID
 * @param int $ch_id 현재 캐릭터 ID
 * @return array|null
 */
function get_timeline_post($tl_table, $wr_id, $ch_id) {
    global $g5;
    
    $write_table = get_timeline_write_table($tl_table);
    $wr_id = (int)$wr_id;
    $ch_id = (int)$ch_id;
    
    // 클릭한 글 조회
    $write = sql_fetch("
        SELECT w.*, 
               c.ch_name, c.ch_thumb,
               (SELECT COUNT(*) FROM {$g5['timeline_good_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$ch_id}') AS my_like,
               (SELECT COUNT(*) FROM {$g5['timeline_bookmark_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$ch_id}') AS my_bookmark
        FROM {$write_table} w
        LEFT JOIN {$g5['character_table']} c ON c.ch_id = w.ch_id
        WHERE w.wr_id = '{$wr_id}'
    ");
    
    if (!$write || !isset($write['wr_id']) || !$write['wr_id']) return null;
    
    // 댓글인 경우 (wr_parent > 0) 스레드 역추적 로직 적용
    if ($write['wr_parent'] > 0) {
        $origin_id = (int)$write['wr_parent'];
        
        // 원글 조회
        $origin = sql_fetch("
            SELECT w.*, 
                   c.ch_name, c.ch_thumb,
                   (SELECT COUNT(*) FROM {$g5['timeline_good_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$ch_id}') AS my_like,
                   (SELECT COUNT(*) FROM {$g5['timeline_bookmark_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$ch_id}') AS my_bookmark
            FROM {$write_table} w
            LEFT JOIN {$g5['character_table']} c ON c.ch_id = w.ch_id
            WHERE w.wr_id = '{$origin_id}'
        ");
        
        if (!$origin || !isset($origin['wr_id'])) return null;
        
        // 결과 구조 설정
        $origin['is_thread_view'] = true;
        $origin['focused_id'] = $wr_id;
        $origin['focused_post'] = $write;  // 클릭한 글 정보
        
        // === 1. 이전 멘션 역추적 (wr_reply_to 체인, 최대 3개) ===
        $before_replies = array();
        $current_reply_to = (int)ses($write, 'wr_reply_to', 0, 'int');
        $trace_count = 0;
        $max_trace = 3;
        
        while ($current_reply_to > 0 && $trace_count < $max_trace) {
            // 원글에 도달하면 멈춤 (current_reply_to가 원글 ID와 같으면)
            if ($current_reply_to == $origin_id) break;
            
            $prev = sql_fetch("
                SELECT w.*, 
                       c.ch_name, c.ch_thumb,
                       (SELECT COUNT(*) FROM {$g5['timeline_good_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$ch_id}') AS my_like,
                       (SELECT COUNT(*) FROM {$g5['timeline_bookmark_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$ch_id}') AS my_bookmark
                FROM {$write_table} w
                LEFT JOIN {$g5['character_table']} c ON c.ch_id = w.ch_id
                WHERE w.wr_id = '{$current_reply_to}' AND w.wr_deleted = 0
            ");
            
            if (!$prev || !isset($prev['wr_id'])) break;
            
            // 앞에 추가 (역순이므로)
            array_unshift($before_replies, $prev);
            $trace_count++;
            
            // 다음 역추적 대상
            $current_reply_to = (int)ses($prev, 'wr_reply_to', 0, 'int');
            // 원글에 대한 직접 답글이면 멈춤 (wr_reply_to = origin_id)
            if ($current_reply_to == $origin_id || $current_reply_to == 0) break;
        }
        
        // 가장 위 글이 원글에 대한 직접 답글인지 확인
        $top_is_direct_reply = false;
        $has_more_before = false;
        $more_before_from = 0;  // 더 보기 시작점
        
        if (count($before_replies) > 0) {
            $top_reply_to = (int)ses($before_replies[0], 'wr_reply_to', 0, 'int');
            $top_is_direct_reply = ($top_reply_to == $origin_id || $top_reply_to == 0);
            
            // 1.2: 원글이 아닌 경우 (더 이전 멘션이 있음)
            if (!$top_is_direct_reply && $top_reply_to > 0 && $top_reply_to != $origin_id) {
                $has_more_before = true;
                $more_before_from = $before_replies[0]['wr_id'];  // 더 보기 기준점
            }
        } else {
            // before_replies가 없는 경우: 클릭한 글이 원글에 대한 직접 답글인지 확인
            $clicked_reply_to = (int)ses($write, 'wr_reply_to', 0, 'int');
            $top_is_direct_reply = ($clicked_reply_to == $origin_id || $clicked_reply_to == 0);
            if (!$top_is_direct_reply && $clicked_reply_to > 0 && $clicked_reply_to != $origin_id) {
                $has_more_before = true;
                $more_before_from = $wr_id;
            }
        }
        
        // === 2. 이후 답글 조회 (내 글에 대한 직접 답글만) ===
        $after_replies = array();
        $res = sql_query("
            SELECT w.*, 
                   c.ch_name, c.ch_thumb,
                   (SELECT COUNT(*) FROM {$g5['timeline_good_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$ch_id}') AS my_like,
                   (SELECT COUNT(*) FROM {$g5['timeline_bookmark_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$ch_id}') AS my_bookmark
            FROM {$write_table} w
            LEFT JOIN {$g5['character_table']} c ON c.ch_id = w.ch_id
            WHERE w.wr_parent = '{$origin_id}' 
              AND w.wr_reply_to = '{$wr_id}'
              AND w.wr_deleted = 0
            ORDER BY w.wr_datetime ASC
        ");
        
        while ($row = sql_fetch_array($res)) {
            $after_replies[] = $row;
        }
        
        // === 폴백: wr_reply_to 체인이 없을 경우 wr_mention_root로 조회 ===
        $mention_root = (int)ses($write, 'wr_mention_root', 0, 'int');
        if (count($before_replies) == 0 && count($after_replies) == 0 && $mention_root > 0) {
            // 같은 wr_mention_root를 가진 모든 댓글 조회
            $res = sql_query("
                SELECT w.*, 
                       c.ch_name, c.ch_thumb,
                       (SELECT COUNT(*) FROM {$g5['timeline_good_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$ch_id}') AS my_like,
                       (SELECT COUNT(*) FROM {$g5['timeline_bookmark_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$ch_id}') AS my_bookmark
                FROM {$write_table} w
                LEFT JOIN {$g5['character_table']} c ON c.ch_id = w.ch_id
                WHERE w.wr_parent = '{$origin_id}' 
                  AND w.wr_mention_root = '{$mention_root}'
                  AND w.wr_deleted = 0
                ORDER BY w.wr_datetime ASC
            ");
            
            $all_thread_replies = array();
            while ($row = sql_fetch_array($res)) {
                $all_thread_replies[] = $row;
            }
            
            // 클릭한 글 기준으로 before/after 분리
            $before_replies = array();
            $after_replies = array();
            $found_clicked = false;
            
            foreach ($all_thread_replies as $r) {
                if ($r['wr_id'] == $wr_id) {
                    $found_clicked = true;
                    continue;  // 클릭한 글은 $write에 이미 있음
                }
                
                if (!$found_clicked) {
                    $before_replies[] = $r;
                } else {
                    $after_replies[] = $r;
                }
            }
            
            // before가 3개 초과면 더 보기 박스 표시
            if (count($before_replies) > 3) {
                $has_more_before = true;
                $more_before_from = $before_replies[count($before_replies) - 3]['wr_id'];
                $before_replies = array_slice($before_replies, -3);  // 마지막 3개만
            }
        }
        
        // === 결과 조합 ===
        // replies 배열: before_replies + 클릭한 글 + after_replies
        $origin['replies'] = array();
        
        foreach ($before_replies as $r) {
            $origin['replies'][] = $r;
        }
        
        // 클릭한 글 추가
        $origin['replies'][] = $write;
        
        foreach ($after_replies as $r) {
            $origin['replies'][] = $r;
        }
        
        // 메타 정보
        $origin['is_thread_view'] = true;  // 스레드 뷰 플래그
        $origin['focused_id'] = $wr_id;    // 클릭한 글 ID (하이라이트용)
        $origin['has_more_before'] = $has_more_before;
        $origin['more_before_from'] = $more_before_from;
        $origin['top_is_direct_reply'] = $top_is_direct_reply;
        
        return $origin;
    }
    
    // 원글인 경우 (wr_parent <= 0): 원글에 직접 달린 댓글만 표시
    $origin_id = $write['wr_id'];
    
    // 댓글 목록 (wr_reply_to = origin_id 또는 0 = 원글에 직접 달린 댓글)
    $write['replies'] = array();
    $write['is_thread_view'] = false;
    
    $res = sql_query("
        SELECT w.*, 
               c.ch_name, c.ch_thumb,
               (SELECT COUNT(*) FROM {$g5['timeline_good_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$ch_id}') AS my_like,
               (SELECT COUNT(*) FROM {$g5['timeline_bookmark_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$ch_id}') AS my_bookmark
        FROM {$write_table} w
        LEFT JOIN {$g5['character_table']} c ON c.ch_id = w.ch_id
        WHERE w.wr_parent = '{$origin_id}' 
          AND w.wr_deleted = 0
          AND (w.wr_reply_to = '{$origin_id}' OR w.wr_reply_to = 0)
        ORDER BY w.wr_datetime ASC
    ");
    
    while ($row = sql_fetch_array($res)) {
        $write['replies'][] = $row;
    }
    
    return $write;
}

/**
 * 스레드 더 보기 (wr_reply_to 역추적)
 * @param string $tl_table 타임라인 테이블명
 * @param int $from_wr_id 기준 글 ID (이 글의 wr_reply_to부터 역추적)
 * @param int $ch_id 캐릭터 ID
 * @param int $limit 가져올 개수 (기본 5)
 * @return array ['replies' => array, 'has_more' => bool, 'next_from' => int]
 */
function get_timeline_thread_more($tl_table, $from_wr_id, $ch_id, $limit = 5) {
    global $g5;
    
    $write_table = get_timeline_write_table($tl_table);
    $from_wr_id = (int)$from_wr_id;
    $ch_id = (int)$ch_id;
    $limit = (int)$limit;
    if ($limit < 1) $limit = 5;
    if ($limit > 500) $limit = 500;
    
    // 기준 글 조회
    $from_write = sql_fetch("SELECT wr_reply_to, wr_parent FROM {$write_table} WHERE wr_id = '{$from_wr_id}'");
    if (!$from_write) {
        return array('replies' => array(), 'has_more' => false, 'next_from' => 0);
    }
    
    $origin_id = (int)ses($from_write, 'wr_parent', 0, 'int');
    $current_reply_to = (int)ses($from_write, 'wr_reply_to', 0, 'int');
    $replies = array();
    $trace_count = 0;
    
    while ($current_reply_to > 0 && $trace_count < $limit) {
        // 원글에 대한 직접 답글이면 멈춤 (wr_reply_to = origin_id 또는 0)
        if ($origin_id > 0 && $current_reply_to == $origin_id) {
            $current_reply_to = 0;
            break;
        }

        $prev = sql_fetch("
            SELECT w.*, 
                   c.ch_name, c.ch_thumb,
                   (SELECT COUNT(*) FROM {$g5['timeline_good_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$ch_id}') AS my_like,
                   (SELECT COUNT(*) FROM {$g5['timeline_bookmark_table']} WHERE tl_table = '{$tl_table}' AND wr_id = w.wr_id AND ch_id = '{$ch_id}') AS my_bookmark
            FROM {$write_table} w
            LEFT JOIN {$g5['character_table']} c ON c.ch_id = w.ch_id
            WHERE w.wr_id = '{$current_reply_to}' AND w.wr_deleted = 0
        ");
        
        if (!$prev || !isset($prev['wr_id'])) break;
        if ($origin_id > 0 && (int)$prev['wr_id'] == $origin_id) {
            $current_reply_to = 0;
            break;
        }
        if ((int)ses($prev, 'wr_parent', 0, 'int') <= 0) {
            $current_reply_to = 0;
            break;
        }
        
        // 앞에 추가
        array_unshift($replies, $prev);
        $trace_count++;
        
        $current_reply_to = (int)ses($prev, 'wr_reply_to', 0, 'int');
        if ($current_reply_to == 0 || ($origin_id > 0 && $current_reply_to == $origin_id)) {
            $current_reply_to = 0;
            break;
        }
    }
    
    // 더 있는지 확인
    $has_more = false;
    $next_from = 0;
    if (count($replies) > 0 && $current_reply_to > 0) {
        $has_more = true;
        $next_from = $replies[0]['wr_id'];
    }
    
    return array(
        'replies' => $replies,
        'has_more' => $has_more,
        'next_from' => $next_from
    );
}


// =====================================================
// 좋아요/북마크 함수
// =====================================================

/**
 * 좋아요 토글
 * @param string $tl_table 타임라인 테이블명
 * @param int $wr_id 글 ID
 * @param int $ch_id 캐릭터 ID
 * @return array ['success' => bool, 'liked' => bool, 'count' => int]
 */
function toggle_timeline_like($tl_table, $wr_id, $ch_id) {
    global $g5;
    
    $write_table = get_timeline_write_table($tl_table);
    $wr_id = (int)$wr_id;
    $ch_id = (int)$ch_id;
    $time = G5_TIME_YMDHIS;
    
    $exists = sql_fetch("SELECT tl_good_id FROM {$g5['timeline_good_table']} 
                         WHERE tl_table = '{$tl_table}' AND wr_id = '{$wr_id}' AND ch_id = '{$ch_id}'");
    
    if ($exists['tl_good_id']) {
        sql_query("DELETE FROM {$g5['timeline_good_table']} WHERE tl_good_id = '{$exists['tl_good_id']}'");
        sql_query("UPDATE {$write_table} SET wr_like_count = wr_like_count - 1, wr_good = wr_good - 1 WHERE wr_id = '{$wr_id}'");
        $liked = false;
    } else {
        sql_query("INSERT INTO {$g5['timeline_good_table']} SET 
                   tl_table = '{$tl_table}', wr_id = '{$wr_id}', ch_id = '{$ch_id}', tl_good_datetime = '{$time}'");
        sql_query("UPDATE {$write_table} SET wr_like_count = wr_like_count + 1, wr_good = wr_good + 1 WHERE wr_id = '{$wr_id}'");
        $liked = true;
    }
    
    $row = sql_fetch("SELECT wr_like_count FROM {$write_table} WHERE wr_id = '{$wr_id}'");
    
    return array('success' => true, 'liked' => $liked, 'count' => (int)$row['wr_like_count']);
}


/**
 * 북마크 토글
 * @param string $tl_table 타임라인 테이블명
 * @param int $wr_id 글 ID
 * @param int $ch_id 캐릭터 ID
 * @return array ['success' => bool, 'bookmarked' => bool]
 */
function toggle_timeline_bookmark($tl_table, $wr_id, $ch_id) {
    global $g5;
    
    $write_table = get_timeline_write_table($tl_table);
    $wr_id = (int)$wr_id;
    $ch_id = (int)$ch_id;
    $time = G5_TIME_YMDHIS;
    
    $exists = sql_fetch("SELECT tl_bm_id FROM {$g5['timeline_bookmark_table']} 
                         WHERE tl_table = '{$tl_table}' AND wr_id = '{$wr_id}' AND ch_id = '{$ch_id}'");
    
    if ($exists['tl_bm_id']) {
        sql_query("DELETE FROM {$g5['timeline_bookmark_table']} WHERE tl_bm_id = '{$exists['tl_bm_id']}'");
        sql_query("UPDATE {$write_table} SET wr_bookmark_count = wr_bookmark_count - 1 WHERE wr_id = '{$wr_id}'");
        $bookmarked = false;
    } else {
        sql_query("INSERT INTO {$g5['timeline_bookmark_table']} SET 
                   tl_table = '{$tl_table}', wr_id = '{$wr_id}', ch_id = '{$ch_id}', tl_bm_datetime = '{$time}'");
        sql_query("UPDATE {$write_table} SET wr_bookmark_count = wr_bookmark_count + 1 WHERE wr_id = '{$wr_id}'");
        $bookmarked = true;
    }
    
    return array('success' => true, 'bookmarked' => $bookmarked);
}


// =====================================================
// 멘션 함수
// =====================================================

/**
 * 타임라인 표시 이름 (tu_name, 없으면 ch_name)
 */
function get_timeline_user_display_name($tl_table, $ch_id) {
    global $g5;
    $ch_id = (int)$ch_id;
    if ($ch_id <= 0) return '';

    $tl_safe = sql_escape_string((string)$tl_table);
    $name = '';
    if ($tl_safe !== '' && !empty($g5['timeline_user_table'])) {
        $row = sql_fetch("
            SELECT tu_name
            FROM {$g5['timeline_user_table']}
            WHERE tl_table = '{$tl_safe}' AND ch_id = '{$ch_id}'
        ", false);
        $name = is_array($row) ? ses($row, 'tu_name', '', 'raw') : '';
    }
    if ($name === '') {
        $ch = sql_fetch("SELECT ch_name FROM {$g5['character_table']} WHERE ch_id = '{$ch_id}'");
        $name = is_array($ch) ? ses($ch, 'ch_name', '', 'raw') : '';
    }
    return $name;
}

/**
 * 캐릭터의 타임라인 멘션 ID (tu_men_id)
 * 사용자 행이 없으면 ch_name 폴백
 */
function get_timeline_user_men_id($tl_table, $ch_id) {
    global $g5;
    static $cache = array();

    $tl_key = (string)$tl_table;
    $ch_id = (int)$ch_id;
    if ($ch_id <= 0) return '';

    $ck = $tl_key.'|'.$ch_id;
    if (array_key_exists($ck, $cache)) {
        return $cache[$ck];
    }

    $men = '';
    $tl_esc = sql_escape_string($tl_key);
    if ($tl_esc !== '' && !empty($g5['timeline_user_table'])) {
        $row = sql_fetch("
            SELECT tu_men_id
            FROM {$g5['timeline_user_table']}
            WHERE tl_table = '{$tl_esc}' AND ch_id = '{$ch_id}'
        ", false);
        $men = is_array($row) ? ses($row, 'tu_men_id', '', 'raw') : '';
    }

    if ($men === '') {
        $ch = sql_fetch("SELECT ch_name FROM {$g5['character_table']} WHERE ch_id = '{$ch_id}'");
        $men = is_array($ch) ? ses($ch, 'ch_name', '', 'raw') : '';
    }

    $cache[$ck] = $men;
    return $men;
}

/**
 * 이름 옆 현재 멘션 ID HTML (@아이디)
 */
function timeline_html_men_handle($tl_table, $ch_id) {
    $men = get_timeline_user_men_id($tl_table, $ch_id);
    if ($men === '') {
        return '';
    }
    return '<span class="tl-handle">@'.h($men).'</span>';
}

/**
 * 멘션 문자열 → ch_id (tu_men_id 우선, 없으면 tu_name)
 */
function resolve_timeline_mention_ch_id($tl_table, $mention_name) {
    global $g5;
    $mention_name = trim((string)$mention_name);
    if ($mention_name === '') return 0;

    $safe = sql_escape_string($mention_name);
    $tl = sql_escape_string((string)$tl_table);

    if ($tl !== '' && !empty($g5['timeline_user_table'])) {
        $row = sql_fetch("
            SELECT ch_id
            FROM {$g5['timeline_user_table']}
            WHERE tl_table = '{$tl}'
              AND tu_men_id = '{$safe}'
              AND tu_men_id != ''
            LIMIT 1
        ", false);
        if (is_array($row) && !empty($row['ch_id'])) {
            return (int)$row['ch_id'];
        }

        $row = sql_fetch("
            SELECT ch_id
            FROM {$g5['timeline_user_table']}
            WHERE tl_table = '{$tl}'
              AND tu_name = '{$safe}'
              AND tu_name != ''
            LIMIT 1
        ", false);
        if (is_array($row) && !empty($row['ch_id'])) {
            return (int)$row['ch_id'];
        }
    }

    return 0;
}

/**
 * 멘션 파싱 (@멘션ID, @"멘션 ID", [[멘션ID]] 형식 지원)
 * @param string $content 글 내용
 * @param string $tl_table 타임라인 테이블명
 * @return array 멘션된 캐릭터 ID 배열
 */
function parse_timeline_mentions($content, $tl_table = '') {
    global $g5;

    if ($tl_table === '') {
        $tl_table = isset($GLOBALS['tl_table']) ? (string)$GLOBALS['tl_table'] : '';
    }

    $mentions = array();
    $names = array();

    // @"멘션 ID" 형식 (공백 포함)
    if (preg_match_all('/@"([^"]+)"/', $content, $matches)) {
        foreach ($matches[1] as $name) {
            $names[] = trim($name);
        }
    }

    // @멘션ID 형식 (공백 없는 경우)
    if (preg_match_all('/@([\w\x{ac00}-\x{d7a3}]+)/u', $content, $matches)) {
        foreach ($matches[1] as $name) {
            $names[] = trim($name);
        }
    }

    // [[멘션ID]] 형식 (하위 호환)
    if (preg_match_all('/\[\[([^\]]+)\]\]/', $content, $matches)) {
        foreach ($matches[1] as $name) {
            $names[] = trim($name);
        }
    }

    $names = array_unique($names);
    foreach ($names as $name) {
        $ch_id = resolve_timeline_mention_ch_id($tl_table, $name);
        if ($ch_id > 0) {
            $mentions[] = $ch_id;
        }
    }

    return array_unique($mentions);
}


/**
 * 멘션 등록 + 통합 알림(push_call, bc_type=mention)
 * @param string $tl_table 타임라인 테이블명
 * @param int $wr_id 글 ID
 * @param int $ch_id 대상 캐릭터 ID
 */
function insert_timeline_mention($tl_table, $wr_id, $ch_id) {
    global $g5, $member, $character;
    
    $wr_id = (int)$wr_id;
    $ch_id = (int)$ch_id;
    if ($wr_id <= 0 || $ch_id <= 0) {
        return;
    }

    // 본인 멘션은 알림/미읽음 행을 만들지 않음 (답글 자동 @멘션 포함)
    $sender_ch_id = ses($character, 'ch_id', 0, 'int');
    if ($sender_ch_id > 0 && $ch_id === $sender_ch_id) {
        return;
    }

    $time = G5_TIME_YMDHIS;
    
    // 중복 체크
    $exists = sql_fetch("SELECT tl_mt_id FROM {$g5['timeline_mention_table']} 
                         WHERE tl_table = '{$tl_table}' AND wr_id = '{$wr_id}' AND ch_id = '{$ch_id}'");
    if (ses($exists, 'tl_mt_id', 0, 'int')) {
        return;
    }
    
    sql_query("
        INSERT INTO {$g5['timeline_mention_table']} SET
            tl_table = '{$tl_table}',
            wr_id = '{$wr_id}',
            ch_id = '{$ch_id}',
            tl_mt_datetime = '{$time}'
    ");
    
    // 통합 알림 (ajax/load_call.php 폴링) — push_call 전용
    if (!function_exists('push_call')) {
        return;
    }
    
    if (function_exists('ensure_call_type')) {
        ensure_call_type(
            'mention',
            '멘션',
            '타임라인에서 멘션되었습니다.',
            '/timeline/view.php?tl_table={tl_table}&wr_id={wr_id}',
            1
        );
    }
    
    $target_ch = sql_fetch("SELECT mb_id, ch_name FROM {$g5['character_table']} WHERE ch_id = '{$ch_id}'");
    $re_mb_id = ses($target_ch, 'mb_id', '', 'raw');
    if ($re_mb_id === '') {
        return;
    }
    
    $sender_mb_id = ses($member, 'mb_id', '', 'raw');
    $sender_ch_id = ses($character, 'ch_id', 0, 'int');
    if ($sender_mb_id === '' || $sender_ch_id <= 0) {
        $write_table = get_timeline_write_table($tl_table);
        $row = sql_fetch("SELECT mb_id, ch_id FROM {$write_table} WHERE wr_id = '{$wr_id}'");
        if ($sender_mb_id === '') {
            $sender_mb_id = ses($row, 'mb_id', '', 'raw');
        }
        if ($sender_ch_id <= 0) {
            $sender_ch_id = ses($row, 'ch_id', 0, 'int');
        }
    }
    if ($sender_mb_id === '' || $sender_mb_id === $re_mb_id) {
        return;
    }

    // 보낸이: timeline_user.tu_name (없으면 캐릭터명 → 회원 닉/이름)
    $sender_name = '';
    $tl_safe = sql_escape_string($tl_table);
    if ($sender_ch_id > 0 && !empty($g5['timeline_user_table'])) {
        $tu_row = sql_fetch("
            SELECT tu_name FROM {$g5['timeline_user_table']}
            WHERE tl_table = '{$tl_safe}' AND ch_id = '{$sender_ch_id}'
        ", false);
        $sender_name = (is_array($tu_row)) ? ses($tu_row, 'tu_name', '', 'raw') : '';
    }
    if ($sender_name === '') {
        $sender_name = ses($character, 'ch_name', '', 'raw');
    }
    if ($sender_name === '' && $sender_ch_id > 0) {
        $sender_ch = sql_fetch("SELECT ch_name FROM {$g5['character_table']} WHERE ch_id = '{$sender_ch_id}'");
        $sender_name = is_array($sender_ch) ? ses($sender_ch, 'ch_name', '', 'raw') : '';
    }
    if ($sender_name === '') {
        $sender_name = ses($member, 'mb_nick', '', 'raw');
    }
    if ($sender_name === '') {
        $sender_name = ses($member, 'mb_name', '', 'raw');
    }

    $timeline_row = function_exists('get_timeline') ? get_timeline($tl_table) : null;
    $tl_subject = is_array($timeline_row) ? ses($timeline_row, 'tl_subject', '', 'raw') : '';
    if ($tl_subject === '') {
        $tl_subject = '타임라인';
    }

    $bc_link = G5_URL.'/timeline/view.php?tl_table='.urlencode($tl_table).'&wr_id='.$wr_id;
    $memo = '['.$tl_subject.'] 타임라인에서 멘션되었습니다.';
    
    push_call(
        'mention',
        array('mb_id' => $sender_mb_id, 'mb_name' => $sender_name),
        array($re_mb_id),
        $memo,
        $bc_link,
        array('tl_table' => $tl_table, 'wr_id' => $wr_id),
        array(
            'bo_table' => $tl_table,
            'wr_id' => $wr_id,
            'ch_side' => ses($character, 'ch_side', '0', 'raw'),
        )
    );
}


/**
 * 새 글/멘션 카운트 (폴링용)
 * 새 알람 규칙: 1.나를 호출한 댓글 2.원글(퍼블릭 글 wr_parent < 0)만 카운트
 * 본인(같은 회원)이 올린 원글은 새 글 알림에서 제외
 * @param string $tl_table 타임라인 테이블명
 * @param int $ch_id 캐릭터 ID
 * @param int $last_check 마지막 체크 타임스탬프
 * @param string $type 타입 (all, public, mentions)
 * @return array
 */
function check_timeline_new($tl_table, $ch_id, $last_check, $type = 'all') {
    global $g5, $member;
    
    $write_table = get_timeline_write_table($tl_table);
    $ch_id = (int)$ch_id;
    $last_check = (int)$last_check;
    
    $result = array(
        'success' => true,
        'new_count' => 0,
        'mention_count' => 0,
        'server_time' => (int)G5_SERVER_TIME,
    );
    
    if (!$last_check) {
        return $result;
    }
    
    $last_time = date('Y-m-d H:i:s', $last_check);
    
    // 테이블 존재 확인
    $table_exists = sql_fetch("SHOW TABLES LIKE '{$write_table}'");
    if (!$table_exists) {
        return $result;
    }
    
    // 새 멘션 수 (나를 호출한 댓글). 내가 쓴 글에 대한 셀프 멘션은 제외
    $new_mentions = sql_fetch("
        SELECT COUNT(*) AS cnt FROM {$g5['timeline_mention_table']} m
        WHERE m.tl_table = '{$tl_table}' AND m.ch_id = '{$ch_id}' AND m.tl_mt_read = 0
        AND NOT EXISTS (
            SELECT 1 FROM {$write_table} w WHERE w.wr_id = m.wr_id AND w.ch_id = '{$ch_id}'
        )
    ");
    $result['mention_count'] = (int)$new_mentions['cnt'];
    
    // 본인 글은 새 글 알림에서 제외 (같은 회원의 다른 캐릭터 글 포함)
    $own_sql = '';
    $mb_id = sql_escape_string(ses($member, 'mb_id', ''));
    if ($mb_id !== '') {
        $own_sql = " AND mb_id != '{$mb_id}'";
    } else if ($ch_id > 0) {
        $own_sql = " AND ch_id != '{$ch_id}'";
    }
    
    // 새 글 수 (원글만: wr_parent < 0)
    switch ($type) {
        case 'public':
            // 원글만 카운트 (wr_parent < 0)
            $new_posts = sql_fetch("
                SELECT COUNT(*) AS cnt FROM {$write_table} 
                WHERE wr_parent < 0 AND wr_deleted = 0
                AND wr_datetime > '{$last_time}'
                {$own_sql}
            ");
            break;
        case 'mentions':
            // 멘션만 볼 때는 new_count = 0
            $new_posts = array('cnt' => 0);
            break;
        default:
            // 전체: 원글만 카운트
            $new_posts = sql_fetch("
                SELECT COUNT(*) AS cnt FROM {$write_table} 
                WHERE wr_parent < 0 AND wr_deleted = 0
                AND wr_datetime > '{$last_time}'
                {$own_sql}
            ");
    }
    
    $result['new_count'] = (int)$new_posts['cnt'];
    
    return $result;
}


/**
 * 멘션 읽음 처리
 * @param string $tl_table 타임라인 테이블명
 * @param int $ch_id 캐릭터 ID
 */
function mark_timeline_mentions_read($tl_table, $ch_id) {
    global $g5, $member;
    $ch_id = (int)$ch_id;
    $tl_esc = sql_escape_string($tl_table);
    sql_query("UPDATE {$g5['timeline_mention_table']} SET tl_mt_read = 1 
               WHERE tl_table = '{$tl_esc}' AND ch_id = '{$ch_id}'");

    // 해당 타임라인의 통합 알림(mention)도 함께 해제 → 폴링/알람음 재발생 방지
    $mb_id = isset($member['mb_id']) ? (string)$member['mb_id'] : '';
    if ($mb_id !== '' && function_exists('call_check_clear_mention_timeline')) {
        call_check_clear_mention_timeline($mb_id, $tl_table);
    }
}


// =====================================================
// 유틸리티 함수
// =====================================================

/**
 * 타임라인 콘텐츠 렌더링 (멘션 링크 변환 + 인라인 마크다운)
 * @param string $content 원본 내용
 * @param string $tl_table 타임라인 테이블명 (링크 생성용)
 * @return string
 * 
 * 멘션 형식:
 * - @캐릭터명 (공백 없는 경우)
 * - @"캐릭터 이름" (공백 있는 경우)
 * - [[캐릭터명]] (하위 호환)
 *
 * 인라인 마크다운 (일반 MD와 반대):
 * - *text* → bold
 * - **text** → italic
 * - ***text*** → bold + italic
 */
/**
 * 타임라인 콘텐츠 렌더링 (멘션 링크 변환 + 인라인 마크다운)
 * @param string $content 원본 내용
 * @param string $tl_table 타임라인 테이블명 (링크 생성용)
 * @param bool|int $is_script 스크립트 글이면 HTML 그대로 출력
 * @return string
 * 
 * 멘션 형식:
 * - @캐릭터명 (공백 없는 경우)
 * - @"캐릭터 이름" (공백 있는 경우)
 * - [[캐릭터명]] (하위 호환)
 *
 * 인라인 마크다운:
 * - *text* → bold
 * - **text** → italic
 * - ***text*** → bold + italic
 *
 * 독백:
 * - (text) → <span class="monolog">(text)</span>
 *
 * 일반 링크:
 * - http(s)://... → 새 탭으로 열리는 <a class="other-site-link"> (해시태그/콜링/로그링크는 미구현 범위라 제외)
 */
function render_timeline_content($content, $tl_table = '', $is_script = false) {
    global $g5;

    if ($is_script || $is_script === 1 || $is_script === '1') {
        return render_timeline_script_content($content);
    }
    
    // HTML 특수문자 이스케이프
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

    // 일반 링크(URL) 변환 — 해시태그/콜링/로그링크는 제외, 멘션·마크다운·독백 처리보다 먼저
    // 플레이스홀더로 보호해두고 모든 가공이 끝난 뒤 복원한다 (extend/mmb.lib.php autolink() 참고)
    $tl_autolink_url_map = array();
    $content = timeline_extract_autolink_urls($content, $tl_autolink_url_map);

    // 멘션 변환 콜백 (tu_men_id 우선 해석)
    $mention_callback = function($mention_name) use ($g5, $tl_table) {
        $mention_name = trim($mention_name);
        $ch_id = resolve_timeline_mention_ch_id($tl_table, $mention_name);
        if ($ch_id > 0) {
            $url = G5_URL.'/timeline/profile.php?ch_id='.$ch_id;
            if ($tl_table) {
                $url .= '&amp;tl_table='.$tl_table;
            }
            return '<span class="tl-mention-wrap"><a href="'.$url.'" class="tl-mention">@'.$mention_name.'</a></span>';
        }
        return '<span class="tl-mention-wrap tl-mention-invalid">@'.$mention_name.'</span>';
    };
    
    // @"캐릭터 이름" 형식 (공백 포함, 따옴표로 감싼 경우)
    $content = preg_replace_callback('/@&quot;([^&]+)&quot;/', function($matches) use ($mention_callback) {
        return $mention_callback($matches[1]);
    }, $content);
    
    // @캐릭터명 형식 (공백 없는 경우, 한글/영문/숫자/_ 허용)
    $content = preg_replace_callback('/@([\w가-힣]+)/', function($matches) use ($mention_callback) {
        return $mention_callback($matches[1]);
    }, $content);
    
    // [[캐릭터명]] 형식 (하위 호환)
    $content = preg_replace_callback('/\[\[([^\]]+)\]\]/', function($matches) use ($mention_callback) {
        return $mention_callback($matches[1]);
    }, $content);

    // 인라인 마크다운 (*bold* / **italic** / ***bold+italic***)
    $content = timeline_apply_inline_markdown($content);

    // (독백) → <span class="monolog">(독백)</span>
    $content = timeline_apply_monolog($content);
    
    // 줄바꿈 처리
    $content = nl2br($content);

    // URL 플레이스홀더 복원
    if ($tl_autolink_url_map) {
        $content = strtr($content, $tl_autolink_url_map);
    }

    return $content;
}

/**
 * 플레인 텍스트(htmlspecialchars 적용된 문자열) 안의 일반 URL(http/https)을
 * 새 탭으로 열리는 링크로 변환하기 위해 플레이스홀더로 추출해 둔다.
 * 해시태그(#)/콜링([[ ]])/로그링크(@숫자)는 대상이 아니며, 멘션(@이름)이나
 * 마크다운(*,**), 독백(()) 처리와 충돌하지 않도록 먼저 추출한다.
 * extend/mmb.lib.php 의 autolink() "일반링크 설정"을 참고해 동일한 표기(Link URL)로 통일한다.
 *
 * @param string $content htmlspecialchars 적용된 문자열
 * @param array $placeholders 참조로 전달되는 '플레이스홀더 => <a> HTML' 맵
 * @return string 플레이스홀더로 치환된 문자열
 */
function timeline_extract_autolink_urls($content, &$placeholders) {
    if ($content === '' || $content === null) {
        return $content;
    }

    $i = 0;

    return preg_replace_callback(
        '/https?:\/\/[^\s<>]+/i',
        function ($m) use (&$placeholders, &$i) {
            $url = $m[0];
            $trail = '';

            // URL 끝에 붙은 문장부호/마크다운 기호는 링크 밖으로 분리한다 (짝이 맞는 괄호는 유지)
            while ($url !== '') {
                $last = substr($url, -1);
                if ($last === ')' && substr_count($url, '(') >= substr_count($url, ')')) {
                    break;
                }
                if (strpos(".,!?;:'\")]}*", $last) === false) {
                    break;
                }
                $trail = $last.$trail;
                $url = substr($url, 0, -1);
            }

            if ($url === '') {
                return $m[0];
            }

            $key = "\x1E".'TLURL'.$i."\x1E";
            $placeholders[$key] = '<a href="'.$url.'" target="_blank" rel="noopener noreferrer" class="other-site-link">Link URL</a>';
            $i++;

            return $key.$trail;
        },
        $content
    );
}

/**
 * 본문이 스마트에디터 HTML 스크립트처럼 보이는지
 * @param string $content
 * @return bool
 */
function timeline_content_looks_like_script($content) {
    $c = (string)$content;
    if ($c === '') return false;
    $c = preg_replace('/^\xEF\xBB\xBF/', '', $c);
    $c = ltrim($c);
    // 저장된 이스케이프 복구 후 판별
    if (strpos($c, '\\"') !== false || strpos($c, "\\'") !== false) {
        $c = stripslashes($c);
    }
    if (preg_match('/^&lt;(p|div|h[1-6]|table|ul|ol|span|font|section|article)\b/i', $c)) {
        return true;
    }
    if (preg_match('/^<(p|div|h[1-6]|table|ul|ol|span|font|section|article)\b/i', $c)
        && preg_match('/style\s*=|align\s*=|class\s*=|<(br|b|i|u|strong|em|span|img|font|a)\b/i', $c)) {
        return true;
    }
    return false;
}

/**
 * 스크립트 글 여부
 * @param array $row
 * @return bool
 */
function timeline_row_is_script($row) {
    if (!is_array($row)) return false;
    if (isset($row['wr_script']) && (int)$row['wr_script'] === 1) return true;
    if (!empty($row['wr_script'])) return true;
    return timeline_content_looks_like_script(isset($row['wr_content']) ? $row['wr_content'] : '');
}

/**
 * tl-item 클래스. wr_script 공지와 별도로 모듈이 클래스를 붙일 때 (ORPG se_script 등).
 */
function timeline_item_html_class($class, $row = null) {
    $class = trim((string)$class);
    if (function_exists('mdl_apply_filters')) {
        $class = mdl_apply_filters('timeline_item_class', $class, is_array($row) ? $row : array());
    }
    return $class;
}

/**
 * 상단 이름/아이디/시간 숨기고 수정·삭제만. wr_script 공지와 별도 (ORPG se_script 등).
 * 하단 메뉴·extra 로그는 스킨이 wr_script 로만 가린다.
 */
function timeline_item_compact_header($row = null) {
    $compact = false;
    if (function_exists('mdl_apply_filters')) {
        $compact = (bool) mdl_apply_filters('timeline_item_compact_header', false, is_array($row) ? $row : array());
    }
    return $compact;
}

/**
 * 스크립트 글 본문 — 스마트에디터 HTML 그대로 (script 태그만 제거)
 * @param string $content
 * @return string
 */
function render_timeline_script_content($content) {
    $content = (string)$content;

    // 이중 addslashes / 엔티티 인코딩 복구
    $guard = 0;
    while ($guard < 3 && (strpos($content, '\\"') !== false || strpos($content, "\\'") !== false || strpos($content, '\\\\') !== false)) {
        $content = stripslashes($content);
        $guard++;
    }
    $guard = 0;
    while ($guard < 3 && strpos($content, '&lt;') !== false
        && preg_match('/&lt;(p|div|h[1-6]|table|ul|ol|span|br|b|i|u|strong|em|font|img)\b/i', $content)) {
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        $guard++;
    }

    $content = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $content);
    if (function_exists('cdn_resolve_content')) {
        $content = cdn_resolve_content($content);
    }
    return $content;
}

/**
 * 타임라인 인라인 마크다운 (이미 htmlspecialchars 된 문자열용)
 * *bold* / **italic** / ***bold and italic***
 */
function timeline_apply_inline_markdown($content) {
    if ($content === '' || $content === null) return $content;

    // ***bold and italic***
    $content = preg_replace(
        '/\*\*\*(?!\s)([^\n*]+?)(?<!\s)\*\*\*/u',
        '<strong><em>$1</em></strong>',
        $content
    );
    // **italic**
    $content = preg_replace(
        '/\*\*(?!\s)([^\n*]+?)(?<!\s)\*\*/u',
        '<em>$1</em>',
        $content
    );
    // *bold*
    $content = preg_replace(
        '/\*(?!\s)([^\n*]+?)(?<!\s)\*/u',
        '<strong>$1</strong>',
        $content
    );

    return $content;
}

/**
 * ()로 감싼 구간을 <span class="monolog">로 감싼다. 괄호 문자도 span 안에 포함한다.
 * 중첩 괄호는 안쪽부터 감싼다. 짝이 없는 괄호는 그대로 둔다.
 *
 * @param string $content htmlspecialchars + 마크다운이 적용된 문자열
 * @return string
 */
function timeline_apply_monolog($content) {
    if ($content === '' || $content === null) {
        return $content;
    }

    $placeholders = array();
    $i = 0;
    $guard = 0;

    while ($guard < 50 && preg_match('/\([^()]*\)/u', $content)) {
        $replaced = preg_replace_callback(
            '/\(([^()]*)\)/u',
            function ($m) use (&$placeholders, &$i) {
                $key = "\x1E".'TLMONO'.$i."\x1E";
                $placeholders[$key] = '<span class="monolog">('.$m[1].')</span>';
                $i++;
                return $key;
            },
            $content
        );
        if ($replaced === null) {
            break;
        }
        $content = $replaced;
        $guard++;
    }

    if ($placeholders) {
        foreach (array_reverse($placeholders, true) as $key => $html) {
            $content = str_replace($key, $html, $content);
        }
    }

    return $content;
}


/**
 * 글/댓글 작성 시 콘텐츠 기믹 처리 (저장 전, DB에 결과 반영)
 *
 * 1) [NdM]  — N회 (1~M) 주사위 합산을 [*합*] 으로 치환. 예: [3d6] → [*11*]
 * 2) [A/B/C] — 슬래시 구분 항목 중 하나를 골라 *볼드*. 예: [A/B/C] → [A/ *B* /C] (공백 없이 *항목*)
 *
 * 한 글에 여러 번·문장 중간이어도 모두 처리. 수정(update) 경로에서는 호출하지 않음.
 *
 * @param string $content
 * @return string
 */
function timeline_process_content_gimmicks($content) {
    if ($content === null || $content === '') {
        return (string)$content;
    }
    $content = (string)$content;

    // 1) 주사위 [숫자d숫자] (소문자 d만)
    $replaced = preg_replace_callback('/\[(\d+)d(\d+)\]/', function ($m) {
        $n = (int)$m[1];
        $sides = (int)$m[2];
        if ($n < 1 || $sides < 1) {
            return $m[0];
        }
        if ($n > 100) {
            $n = 100;
        }
        if ($sides > 10000) {
            $sides = 10000;
        }
        $sum = 0;
        for ($i = 0; $i < $n; $i++) {
            $sum += mt_rand(1, $sides);
        }
        return '[*'.$sum.'*]';
    }, $content);
    if ($replaced !== null) {
        $content = $replaced;
    }

    // 2) 선택지 [A/B/C...] — 내부에 / 가 있는 대괄호만
    $replaced = preg_replace_callback('/\[([^\[\]]+)\]/u', function ($m) {
        $inner = $m[1];
        if (strpos($inner, '/') === false) {
            return $m[0];
        }
        $parts = explode('/', $inner);
        if (count($parts) < 2) {
            return $m[0];
        }
        $idx = mt_rand(0, count($parts) - 1);
        $parts[$idx] = '*'.$parts[$idx].'*';
        return '['.implode('/', $parts).']';
    }, $content);
    if ($replaced !== null) {
        $content = $replaced;
    }

    return $content;
}


/**
 * 시간 경과 표시
 * @param string $datetime 날짜시간 문자열
 * @return string
 */
function get_time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $diff = G5_SERVER_TIME - $timestamp;
    
    if ($diff < 60) {
        return '방금';
    } elseif ($diff < 3600) {
        return floor($diff / 60).'분 전';
    } elseif ($diff < 86400) {
        return floor($diff / 3600).'시간 전';
    } elseif ($diff < 172800) {
        return '어제';
    } else {
        return date('m월 d일', $timestamp);
    }
}


/**
 * 프로필 이미지 URL 반환 (호환 래퍼)
 * @param array|int $row 캐릭터/글 정보 배열 (ch_id 포함) 또는 ch_id
 * @return string
 */
function get_timeline_profile_image($row) {
    $ch_id = is_array($row) ? (int)ses($row, 'ch_id', 0, 'int') : (int)$row;
    return get_timeline_thumb($ch_id, 1);
}

/**
 * 타임라인용 썸네일 이미지 가져오기
 * timeline_user.tu_thumb 우선, 없으면 시기에 맞는 get_character_head() 사용
 *
 * @param int $ch_id 캐릭터 ID
 * @param int $time 시기 (0=현재, 1=시기1, 2=시기2, ...) — 폴백용
 * @return string 이미지 URL
 */
function get_timeline_thumb($ch_id, $time = 0) {
    global $g5, $tl_table, $commu_time;

    $ch_id = (int)$ch_id;
    $fallback = G5_IMG_URL.'/no_profile.png';
    if ($ch_id <= 0) return $fallback;

    $thumb = '';
    $tl = isset($tl_table) ? sql_escape_string((string)$tl_table) : '';
    if ($tl !== '' && !empty($g5['timeline_user_table'])) {
        $urow = sql_fetch("
            SELECT tu_thumb
            FROM {$g5['timeline_user_table']}
            WHERE tl_table = '{$tl}' AND ch_id = '{$ch_id}'
        ", false);
        if (is_array($urow)) {
            $thumb = ses($urow, 'tu_thumb', '', 'raw');
        }
    }

    if ($thumb !== '') {
        // URL / 절대경로 / data URL이면 그대로
        if (
            strpos($thumb, 'http://') === 0
            || strpos($thumb, 'https://') === 0
            || (isset($thumb[0]) && $thumb[0] === '/')
            || (defined('G5_DATA_URL') && strpos($thumb, G5_DATA_URL) === 0)
        ) {
            $local = timeline_thumb_url_to_local_path($thumb);
            if ($local !== null && !is_file($local)) {
                $thumb = '';
            } else {
                return $thumb;
            }
        } else {
            // 파일명만 있는 경우
            return G5_DATA_URL.'/timeline/thumbs/'.$thumb;
        }
    }

    // 사용자 행/썸네일 없으면 캐릭터 썸네일 폴백
    if (!$time) $time = $commu_time ? $commu_time : 1;

    if (function_exists('get_character_head')) {
        $head = get_character_head($ch_id, $time);
        if ($head) return $head;
    }

    return $fallback;
}


// =====================================================
// 호환성 래퍼 함수 (기존 AJAX 파일용)
// =====================================================

/**
 * insert_timeline - 글 작성 래퍼 (기존 호환)
 */
function insert_timeline($ch_id, $content, $parent_id = 0, $image = '') {
    global $tl_table;
    $tl = $tl_table ? $tl_table : 'default';
    
    return insert_timeline_post($tl, $ch_id, $content, array(
        'parent_id' => $parent_id
    ));
}


/**
 * toggle_like - 좋아요 래퍼 (기존 호환)
 */
function toggle_like($ch_id, $wr_id) {
    global $tl_table;
    $tl = $tl_table ? $tl_table : 'default';
    return toggle_timeline_like($tl, $wr_id, $ch_id);
}


/**
 * toggle_bookmark - 북마크 래퍼 (기존 호환)
 */
function toggle_bookmark($ch_id, $wr_id) {
    global $tl_table;
    $tl = $tl_table ? $tl_table : 'default';
    return toggle_timeline_bookmark($tl, $wr_id, $ch_id);
}


/**
 * check_new_posts - 새 글 체크 래퍼 (기존 호환)
 */
function check_new_posts($ch_id, $last_check, $type = 'all') {
    global $tl_table;
    $tl = $tl_table ? $tl_table : 'default';
    return check_timeline_new($tl, $ch_id, $last_check, $type);
}


/**
 * delete_timeline - 글 삭제 래퍼 (기존 호환)
 */
function delete_timeline($wr_id, $ch_id) {
    global $tl_table, $is_admin;
    $tl = $tl_table ? $tl_table : 'default';
    return delete_timeline_post($tl, $wr_id, $ch_id, ($is_admin));
}

// =====================================================
// DM (Direct Message) 시스템
// =====================================================

/**
 * DM AJAX 공통: 로그인·활성 캐릭터·DM 사용 권한
 * @return int 활성 ch_id
 */
function timeline_dm_ajax_require() {
    global $character, $is_member, $is_admin, $tl_table;

    $my_ch_id = ses($character, 'ch_id', 0, 'int');
    if (!$is_member || !$my_ch_id) {
        echo json_encode(array('success' => false, 'message' => '로그인이 필요합니다.'));
        exit;
    }
    if (!can_use_timeline_dm($tl_table, $my_ch_id, $is_admin)) {
        echo json_encode(array('success' => false, 'message' => '이 타임라인의 DM을 사용할 수 없습니다.'));
        exit;
    }
    return $my_ch_id;
}

/**
 * 메시지 행에 표시명/아바타 채움
 * 스냅샷(msg_name/msg_thumb) 우선, 없으면 현재 tu_name / get_timeline_thumb
 */
function hydrate_dm_message_row($row, $my_ch_id) {
    $name = is_array($row) ? ses($row, 'msg_name', '', 'raw') : '';
    if ($name === '') {
        $name = ses($row, 'tu_name', '', 'raw');
    }
    if ($name === '') {
        $name = ses($row, 'ch_name', '', 'raw');
    }
    $thumb = ses($row, 'msg_thumb', '', 'raw');
    if ($thumb === '') {
        $thumb = get_timeline_thumb((int)ses($row, 'ch_id', 0, 'int'), 1);
    }
    $row['ch_name'] = $name;
    $row['avatar'] = $thumb;
    $row['is_mine'] = ((int)ses($row, 'ch_id', 0, 'int') === (int)$my_ch_id);
    return $row;
}

/**
 * DM 초대 후보 (timeline_user.tu_name, 아바타는 get_timeline_thumb)
 */
function get_dm_candidate_users($tl_table, $exclude_ch_id = 0) {
    global $g5;

    $tl_esc = sql_escape_string((string)$tl_table);
    $exclude_ch_id = (int)$exclude_ch_id;
    $users = array();
    if ($tl_esc === '') {
        return $users;
    }

    $where_ex = $exclude_ch_id > 0 ? "AND tu.ch_id != '{$exclude_ch_id}'" : '';
    $result = sql_query("
        SELECT tu.ch_id, tu.tu_name, c.ch_name
        FROM {$g5['timeline_user_table']} tu
        LEFT JOIN {$g5['character_table']} c ON c.ch_id = tu.ch_id
        WHERE tu.tl_table = '{$tl_esc}'
          {$where_ex}
          AND (c.ch_id IS NULL OR c.ch_state != '삭제')
        ORDER BY tu.tu_name ASC, c.ch_name ASC
    ");
    if (!$result) {
        return $users;
    }

    $GLOBALS['tl_table'] = $tl_table;
    while ($row = sql_fetch_array($result)) {
        $ch_id = (int)ses($row, 'ch_id', 0, 'int');
        $name = ses($row, 'tu_name', '', 'raw');
        if ($name === '') {
            $name = ses($row, 'ch_name', '', 'raw');
        }
        $users[] = array(
            'ch_id' => $ch_id,
            'name' => $name,
            'avatar' => get_timeline_thumb($ch_id, 1),
        );
    }
    return $users;
}

/**
 * DM 방 생성
 */
function create_dm_room($tl_table, $creator_ch_id, $participant_ch_ids, $room_name = '') {
    global $g5;

    $creator_ch_id = (int)$creator_ch_id;
    if (!$tl_table || !$creator_ch_id || empty($participant_ch_ids)) {
        return array('success' => false, 'message' => '참가자를 선택해주세요.');
    }

    $participant_ch_ids = array_map('intval', $participant_ch_ids);
    if (!in_array($creator_ch_id, $participant_ch_ids, true)) {
        $participant_ch_ids[] = $creator_ch_id;
    }
    $participant_ch_ids = array_values(array_unique($participant_ch_ids));

    $valid = array();
    foreach ($participant_ch_ids as $ch_id) {
        if ($ch_id === $creator_ch_id || is_valid_dm_participant($tl_table, $ch_id)) {
            $valid[] = $ch_id;
        }
    }
    $participant_ch_ids = $valid;

    if (count($participant_ch_ids) < 2) {
        return array('success' => false, 'message' => '이 타임라인 사용자 중 최소 1명을 선택해주세요.');
    }

    $tl_esc = sql_escape_string($tl_table);
    $room_name_escaped = $room_name ? sql_escape_string(trim($room_name)) : '';
    $now = G5_TIME_YMDHIS;

    $insert_result = sql_query("
        INSERT INTO {$g5['timeline_dm_rooms']}
        SET tl_table = '{$tl_esc}',
            room_name = '{$room_name_escaped}',
            created_by = '{$creator_ch_id}',
            created_at = '{$now}'
    ");

    if (!$insert_result) {
        return array('success' => false, 'message' => '방 생성에 실패했습니다.');
    }

    $room_id = sql_insert_id();
    if (!$room_id) {
        return array('success' => false, 'message' => '방 생성에 실패했습니다.');
    }

    foreach ($participant_ch_ids as $ch_id) {
        $ch_id = (int)$ch_id;
        $is_creator = ($ch_id == $creator_ch_id) ? 1 : 0;
        sql_query("
            INSERT INTO {$g5['timeline_dm_participants']}
            SET room_id = '{$room_id}',
                ch_id = '{$ch_id}',
                joined_at = '{$now}',
                is_creator = '{$is_creator}'
        ");
    }

    return array(
        'success' => true,
        'room_id' => $room_id,
        'message' => 'DM 방이 생성되었습니다.',
    );
}

/**
 * DM 방 목록 조회 (활성 캐릭터가 참가 중인 방만. super도 동일)
 */
function get_dm_rooms($tl_table, $ch_id, $is_admin = false) {
    global $g5;

    $tl_esc = sql_escape_string($tl_table);
    $ch_id = (int)$ch_id;

    $sql = "
        SELECT r.*,
               COUNT(DISTINCT p2.ch_id) as participant_count,
               (SELECT COUNT(*) FROM {$g5['timeline_dm_messages']} m
                WHERE m.room_id = r.room_id
                  AND m.is_deleted = 0
                  AND m.created_at > IFNULL(p.last_read_at, '1970-01-01')) as unread_count
        FROM {$g5['timeline_dm_rooms']} r
        INNER JOIN {$g5['timeline_dm_participants']} p ON p.room_id = r.room_id AND p.ch_id = '{$ch_id}' AND p.is_active = 1
        LEFT JOIN {$g5['timeline_dm_participants']} p2 ON p2.room_id = r.room_id AND p2.is_active = 1
        WHERE r.is_deleted = 0 AND r.tl_table = '{$tl_esc}'
        GROUP BY r.room_id
        ORDER BY r.last_message_at DESC, r.created_at DESC
    ";

    $result = sql_query($sql);
    $rooms = array();
    if (!$result) {
        return $rooms;
    }

    while ($row = sql_fetch_array($result)) {
        if (!$row['room_name']) {
            $row['room_name'] = get_dm_room_auto_name($row['room_id'], $ch_id, $tl_table);
        }
        $rooms[] = $row;
    }

    return $rooms;
}

/**
 * DM 방 자동 이름 (참가자 tu_name)
 */
function get_dm_room_auto_name($room_id, $exclude_ch_id = 0, $tl_table = '') {
    global $g5;

    $room_id = (int)$room_id;
    $exclude_ch_id = (int)$exclude_ch_id;
    $tl_esc = sql_escape_string((string)$tl_table);

    if ($tl_esc === '') {
        $room = sql_fetch("SELECT tl_table FROM {$g5['timeline_dm_rooms']} WHERE room_id = '{$room_id}'");
        $tl_esc = is_array($room) ? sql_escape_string(ses($room, 'tl_table', '', 'raw')) : '';
        $tl_table = $tl_esc;
    }

    $sql = "
        SELECT tu.tu_name, c.ch_name
        FROM {$g5['timeline_dm_participants']} p
        LEFT JOIN {$g5['timeline_user_table']} tu ON tu.ch_id = p.ch_id AND tu.tl_table = '{$tl_esc}'
        LEFT JOIN {$g5['character_table']} c ON c.ch_id = p.ch_id
        WHERE p.room_id = '{$room_id}' AND p.is_active = 1
    ";
    if ($exclude_ch_id) {
        $sql .= " AND p.ch_id != '{$exclude_ch_id}'";
    }
    $sql .= " ORDER BY p.joined_at ASC LIMIT 3";

    $result = sql_query($sql);
    $names = array();
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $name = ses($row, 'tu_name', '', 'raw');
            if ($name === '') {
                $name = ses($row, 'ch_name', '', 'raw');
            }
            if ($name !== '') {
                $names[] = $name;
            }
        }
    }

    if (empty($names)) {
        return '(없음)';
    }

    $count_sql = "
        SELECT COUNT(*) as cnt
        FROM {$g5['timeline_dm_participants']}
        WHERE room_id = '{$room_id}' AND is_active = 1
    ";
    if ($exclude_ch_id) {
        $count_sql .= " AND ch_id != '{$exclude_ch_id}'";
    }

    $count_row = sql_fetch($count_sql);
    $total = (int)$count_row['cnt'];

    $name = implode(', ', $names);
    if ($total > 3) {
        $name .= ' 외 '.($total - 3).'명';
    }

    return $name;
}

/**
 * DM 방 조회 (권한 체크 포함)
 */
function get_dm_room($tl_table, $room_id, $ch_id, $is_admin = false) {
    global $g5;

    $tl_esc = sql_escape_string($tl_table);
    $room_id = (int)$room_id;
    $ch_id = (int)$ch_id;

    $room = sql_fetch("
        SELECT * FROM {$g5['timeline_dm_rooms']}
        WHERE room_id = '{$room_id}' AND is_deleted = 0 AND tl_table = '{$tl_esc}'
    ");

    if (!$room) {
        return null;
    }

    if (!$is_admin) {
        $participant = sql_fetch("
            SELECT * FROM {$g5['timeline_dm_participants']}
            WHERE room_id = '{$room_id}' AND ch_id = '{$ch_id}' AND is_active = 1
        ");
        if (!$participant) {
            return null;
        }
        $room['is_creator'] = $participant['is_creator'];
    } else {
        $participant = sql_fetch("
            SELECT is_creator FROM {$g5['timeline_dm_participants']}
            WHERE room_id = '{$room_id}' AND ch_id = '{$ch_id}' AND is_active = 1
        ");
        $room['is_creator'] = $participant ? $participant['is_creator'] : 0;
    }

    if (!$room['room_name']) {
        $room['room_name'] = get_dm_room_auto_name($room_id, $ch_id, $tl_table);
    }

    return $room;
}

/**
 * DM 메시지 전송 (표시명·썸네일은 작성 시점 스냅샷)
 */
function send_dm_message($tl_table, $room_id, $ch_id, $content) {
    global $g5;

    $room_id = (int)$room_id;
    $ch_id = (int)$ch_id;
    $content = sql_escape_string(trim($content));

    if (!$content) {
        return array('success' => false, 'message' => '메시지를 입력해주세요.');
    }

    $participant = sql_fetch("
        SELECT * FROM {$g5['timeline_dm_participants']}
        WHERE room_id = '{$room_id}' AND ch_id = '{$ch_id}' AND is_active = 1
    ");
    if (!$participant) {
        return array('success' => false, 'message' => '참가자가 아닙니다.');
    }

    $GLOBALS['tl_table'] = $tl_table;
    $msg_name = sql_escape_string(get_timeline_user_display_name($tl_table, $ch_id));
    $msg_thumb = sql_escape_string(get_timeline_post_thumb_url($ch_id));
    $now = G5_TIME_YMDHIS;

    sql_query("
        INSERT INTO {$g5['timeline_dm_messages']}
        SET room_id = '{$room_id}',
            ch_id = '{$ch_id}',
            msg_name = '{$msg_name}',
            msg_thumb = '{$msg_thumb}',
            content = '{$content}',
            created_at = '{$now}'
    ");

    $msg_id = sql_insert_id();

    sql_query("
        UPDATE {$g5['timeline_dm_rooms']}
        SET last_message_at = '{$now}'
        WHERE room_id = '{$room_id}'
    ");

    return array(
        'success' => true,
        'msg_id' => $msg_id,
        'message' => '메시지가 전송되었습니다.',
    );
}

/**
 * DM 메시지 목록
 * $after_msg_id > 0 이면 그 이후(폴링), 아니면 최근 N건(초기 로드, before면 더 이전)
 */
function get_dm_messages($tl_table, $room_id, $ch_id, $limit = 50, $before_msg_id = 0, $after_msg_id = 0, $is_admin = false) {
    global $g5;

    $room_id = (int)$room_id;
    $ch_id = (int)$ch_id;
    $limit = (int)$limit;
    $before_msg_id = (int)$before_msg_id;
    $after_msg_id = (int)$after_msg_id;
    $tl_esc = sql_escape_string((string)$tl_table);

    $participant = sql_fetch("
        SELECT * FROM {$g5['timeline_dm_participants']}
        WHERE room_id = '{$room_id}' AND ch_id = '{$ch_id}' AND is_active = 1
    ");
    if (!$participant && !$is_admin) {
        return array();
    }

    $GLOBALS['tl_table'] = $tl_table;

    $where = "m.room_id = '{$room_id}' AND m.is_deleted = 0";
    $order = 'ORDER BY m.msg_id DESC';
    $reverse = true;
    if ($after_msg_id > 0) {
        $where .= " AND m.msg_id > '{$after_msg_id}'";
        $order = 'ORDER BY m.msg_id ASC';
        $reverse = false;
    } elseif ($before_msg_id > 0) {
        $where .= " AND m.msg_id < '{$before_msg_id}'";
    }

    $sql = "
        SELECT m.*, tu.tu_name, c.ch_name
        FROM {$g5['timeline_dm_messages']} m
        LEFT JOIN {$g5['timeline_user_table']} tu
            ON tu.ch_id = m.ch_id AND tu.tl_table = '{$tl_esc}'
        LEFT JOIN {$g5['character_table']} c ON c.ch_id = m.ch_id
        WHERE {$where}
        {$order}
        LIMIT {$limit}
    ";

    $result = sql_query($sql);
    $messages = array();
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $messages[] = hydrate_dm_message_row($row, $ch_id);
        }
    }

    if ($reverse) {
        $messages = array_reverse($messages);
    }

    if (!empty($messages) && $participant) {
        $now = G5_TIME_YMDHIS;
        sql_query("
            UPDATE {$g5['timeline_dm_participants']}
            SET last_read_at = '{$now}'
            WHERE room_id = '{$room_id}' AND ch_id = '{$ch_id}'
        ");
    }

    return $messages;
}

/**
 * DM 참가자 추가 (timeline_user만, 비활성 참가자는 재활성화)
 */
function add_dm_participant($room_id, $ch_id, $new_ch_ids, $is_admin = false) {
    global $g5;

    $room_id = (int)$room_id;
    $ch_id = (int)$ch_id;

    $room = sql_fetch("SELECT tl_table FROM {$g5['timeline_dm_rooms']} WHERE room_id = '{$room_id}' AND is_deleted = 0");
    if (!$room) {
        return array('success' => false, 'message' => '존재하지 않는 방입니다.');
    }
    $tl_table = ses($room, 'tl_table', '', 'raw');

    if (!$is_admin) {
        $participant = sql_fetch("
            SELECT is_creator FROM {$g5['timeline_dm_participants']}
            WHERE room_id = '{$room_id}' AND ch_id = '{$ch_id}' AND is_active = 1
        ");
        if (!$participant || !$participant['is_creator']) {
            return array('success' => false, 'message' => '권한이 없습니다.');
        }
    }

    $now = G5_TIME_YMDHIS;
    $added = 0;

    foreach ($new_ch_ids as $new_ch_id) {
        $new_ch_id = (int)$new_ch_id;
        if (!is_valid_dm_participant($tl_table, $new_ch_id)) {
            continue;
        }

        $exists = sql_fetch("
            SELECT participant_id, is_active FROM {$g5['timeline_dm_participants']}
            WHERE room_id = '{$room_id}' AND ch_id = '{$new_ch_id}'
        ");

        if ($exists) {
            if (!(int)$exists['is_active']) {
                sql_query("
                    UPDATE {$g5['timeline_dm_participants']}
                    SET is_active = 1, joined_at = '{$now}'
                    WHERE participant_id = '{$exists['participant_id']}'
                ");
                $added++;
            }
        } else {
            sql_query("
                INSERT INTO {$g5['timeline_dm_participants']}
                SET room_id = '{$room_id}',
                    ch_id = '{$new_ch_id}',
                    joined_at = '{$now}',
                    is_creator = 0
            ");
            $added++;
        }
    }

    if ($added < 1) {
        return array('success' => false, 'message' => '추가할 참가자가 없습니다.');
    }

    return array(
        'success' => true,
        'message' => $added.'명의 참가자가 추가되었습니다.',
        'count' => $added,
    );
}

/**
 * DM 참가자 목록 (표시명 tu_name, 아바타 get_timeline_thumb)
 */
function get_dm_participants($tl_table, $room_id) {
    global $g5;

    $room_id = (int)$room_id;
    $tl_esc = sql_escape_string((string)$tl_table);
    $GLOBALS['tl_table'] = $tl_table;

    $sql = "
        SELECT p.*, tu.tu_name, c.ch_name
        FROM {$g5['timeline_dm_participants']} p
        LEFT JOIN {$g5['timeline_user_table']} tu
            ON tu.ch_id = p.ch_id AND tu.tl_table = '{$tl_esc}'
        LEFT JOIN {$g5['character_table']} c ON c.ch_id = p.ch_id
        WHERE p.room_id = '{$room_id}' AND p.is_active = 1
        ORDER BY p.is_creator DESC, p.joined_at ASC
    ";

    $result = sql_query($sql);
    $participants = array();
    if (!$result) {
        return $participants;
    }

    while ($row = sql_fetch_array($result)) {
        $name = ses($row, 'tu_name', '', 'raw');
        if ($name === '') {
            $name = ses($row, 'ch_name', '', 'raw');
        }
        $row['ch_name'] = $name;
        $row['avatar'] = get_timeline_thumb((int)ses($row, 'ch_id', 0, 'int'), 1);
        $participants[] = $row;
    }

    return $participants;
}
