<?php
if (!defined('_GNUBOARD_')) exit;

@ini_set('memory_limit', '-1');

// 게시글리스트 썸네일 생성
function get_list_thumbnail($bo_table, $wr_id, $thumb_width, $thumb_height, $is_create=false, $is_crop=false, $crop_mode='center', $is_sharpen=false, $um_value='80/0.5/3')
{
    global $g5, $config;

    $filename = '';
    $filepath = '';
    $alt = '';
    $edt = false;
    $data_path = '';

    $bo_table = preg_replace('/[^a-zA-Z0-9_]/', '', $bo_table);
    $wr_id = (int)$wr_id;

    $sql = " select bf_file, bf_content 
               from {$g5['board_file_table']}
              where bo_table = '{$bo_table}'
                and wr_id = '{$wr_id}'
                and bf_type between '1' and '3'
           order by bf_no
              limit 0, 1 ";
    $row = sql_fetch($sql);

    if (isset($row['bf_file']) && $row['bf_file']) {
        $filename = $row['bf_file'];
        $filepath = G5_DATA_PATH.'/file/'.$bo_table;
        $alt = get_text(isset($row['bf_content']) ? $row['bf_content'] : '');
    } else {
        $write_table = $g5['write_prefix'].$bo_table;
        $sql = " select wr_content from {$write_table} where wr_id = '{$wr_id}' ";
        $write = sql_fetch($sql);

        if (isset($write['wr_content']) && $write['wr_content'] !== '') {
            $matches = get_editor_image($write['wr_content'], false);
            $edt = true;

            if (is_array($matches) && isset($matches[1]) && is_array($matches[1])) {
                $cnt = count($matches[1]);

                for ($i = 0; $i < $cnt; $i++) {
                    $img_url = $matches[1][$i];
                    if ($img_url === '') {
                        continue;
                    }

                    $p = parse_url($img_url);
                    if (!isset($p['path'])) {
                        continue;
                    }

                    if (strpos($p['path'], '/'.G5_DATA_DIR.'/') != 0) {
                        $data_path = preg_replace('/^\/.*\/'.G5_DATA_DIR.'/', '/'.G5_DATA_DIR, $p['path']);
                    } else {
                        $data_path = $p['path'];
                    }

                    $srcfile = G5_PATH.$data_path;

                    if (!preg_match("/\.({$config['cf_image_extension']})$/i", $srcfile)) {
                        continue;
                    }

                    if (!is_file($srcfile)) {
                        continue;
                    }

                    $size = @getimagesize($srcfile);
                    if (empty($size)) {
                        continue;
                    }

                    $filename = basename($srcfile);
                    $filepath = dirname($srcfile);

                    $malt = array();
                    preg_match("/alt=[\"\']?([^\"\']*)[\"\']?/", $matches[0][$i], $malt);
                    $alt = get_text(isset($malt[1]) ? $malt[1] : '');

                    break;
                }
            }
        }
    }

    if ($filename === '' || $filepath === '') {
        return false;
    }

    $tname = thumbnail($filename, $filepath, $filepath, $thumb_width, $thumb_height, $is_create, $is_crop, $crop_mode, $is_sharpen, $um_value);

    if (!$tname) {
        return false;
    }

    if ($edt) {
        if ($data_path === '') {
            return false;
        }
        $ori = G5_URL.$data_path;
        $src = G5_URL.str_replace($filename, $tname, $data_path);
    } else {
        if (function_exists('cdn_data_url')) {
            $ori = cdn_data_url('file/'.$bo_table.'/'.$filename);
            $src = cdn_data_url('file/'.$bo_table.'/'.$tname);
        } else {
            $ori = G5_DATA_URL.'/file/'.$bo_table.'/'.$filename;
            $src = G5_DATA_URL.'/file/'.$bo_table.'/'.$tname;
        }
    }

    return array(
        'src' => $src,
        'ori' => $ori,
        'alt' => $alt
    );
}

// 게시글보기 썸네일 생성
function get_view_thumbnail($contents, $thumb_width=0)
{
    global $board, $config;

    if (!$thumb_width) {
        $thumb_width = isset($board['bo_image_width']) ? (int)$board['bo_image_width'] : 0;
    }

    if ($thumb_width <= 0) {
        return $contents;
    }

    $matches = get_editor_image($contents, true);

    if (empty($matches) || !isset($matches[0], $matches[1]) || !is_array($matches[0]) || !is_array($matches[1])) {
        return $contents;
    }

    $cnt = count($matches[1]);

    for ($i = 0; $i < $cnt; $i++) {

        $img_tag = $matches[0][$i];
        $img = $matches[1][$i];

        if ($img === '') {
            continue;
        }

        // 기본값 초기화
        $src = '';
        $style = '';
        $width = 0;
        $height = 0;
        $alt = '';

        // src
        $m = array();
        if (preg_match("/src=[\'\"]?([^>\'\"]+[^>\'\"]+)/i", $img, $m) && isset($m[1])) {
            $src = $m[1];
        }

        if ($src === '') {
            continue;
        }

        // style
        $m = array();
        if (preg_match("/style=[\"\']?([^\"\'>]+)/i", $img, $m) && isset($m[1])) {
            $style = $m[1];
        }

        // width
        $m = array();
        if ($style && preg_match("/width:\s*(\d+)px/i", $style, $m) && isset($m[1])) {
            $width = (int)$m[1];
        }

        // height
        $m = array();
        if ($style && preg_match("/height:\s*(\d+)px/i", $style, $m) && isset($m[1])) {
            $height = (int)$m[1];
        }

        // alt
        $m = array();
        if (preg_match("/alt=[\"\']?([^\"\']*)[\"\']?/", $img, $m) && isset($m[1])) {
            $alt = get_text($m[1]);
        }

        // 이미지 path
        $p = parse_url($src);
        if (!isset($p['path'])) {
            continue;
        }

        if (strpos($p['path'], '/'.G5_DATA_DIR.'/') != 0) {
            $data_path = preg_replace('/^\/.*\/'.G5_DATA_DIR.'/', '/'.G5_DATA_DIR, $p['path']);
        } else {
            $data_path = $p['path'];
        }

        $srcfile = G5_PATH.$data_path;

        if (!is_file($srcfile)) {
            continue;
        }

        $size = @getimagesize($srcfile);
        if (empty($size)) {
            continue;
        }

        // jpg exif 회전
        if ($size[2] == 2 && function_exists('exif_read_data')) {
            $degree = 0;
            $exif = @exif_read_data($srcfile);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 8: $degree = 90; break;
                    case 3: $degree = 180; break;
                    case 6: $degree = -90; break;
                }

                if ($degree == 90 || $degree == -90) {
                    $tmp = $size;
                    $size[0] = $tmp[1];
                    $size[1] = $tmp[0];
                }
            }
        }

        // 원본이 더 작으면 건너뜀
        if ($size[0] <= $thumb_width) {
            continue;
        }

        // Animated GIF 체크
        $is_animated = false;
        if ($size[2] == 1) {
            $is_animated = is_animated_gif($srcfile);
        }

        $thumb_height = (int)round(($thumb_width * $size[1]) / $size[0]);
        $filename = basename($srcfile);
        $filepath = dirname($srcfile);

        if (!$is_animated) {
            $thumb_file = thumbnail($filename, $filepath, $filepath, $thumb_width, $thumb_height, false);
        } else {
            $thumb_file = $filename;
        }

        if (!$thumb_file) {
            continue;
        }

        $thumb_src = G5_URL.str_replace($filename, $thumb_file, $data_path);

        if ($width > 0) {
            $thumb_tag = '<img src="'.$thumb_src.'" alt="'.$alt.'" width="'.$width.'" height="'.$height.'"/>';
        } else {
            $thumb_tag = '<img src="'.$thumb_src.'" alt="'.$alt.'"/>';
        }

        // 에디터 업로드 이미지면 원본보기 링크
        if (strpos($img_tag, G5_DATA_DIR.'/'.G5_EDITOR_DIR) !== false
            && preg_match("/\.({$config['cf_image_extension']})$/i", $filename)) {

            $imgurl = str_replace(G5_URL, '', $src);
            $thumb_tag = '<a href="'.G5_BBS_URL.'/view_image.php?fn='.urlencode($imgurl).'" target="_blank" class="view_image">'.$thumb_tag.'</a>';
        }

        $contents = str_replace($img_tag, $thumb_tag, $contents);
    }

    return $contents;
}

function thumbnail($filename, $source_path, $target_path, $thumb_width, $thumb_height, $is_create, $is_crop=false, $crop_mode='center', $is_sharpen=false, $um_value='80/0.5/3')
{
    global $g5;

    $thumb_width = (int)$thumb_width;
    $thumb_height = (int)$thumb_height;

    if ($thumb_width <= 0 && $thumb_height <= 0) {
        return '';
    }

    $source_file = $source_path.'/'.$filename;

    if (!is_file($source_file)) {
        return '';
    }

    $size = @getimagesize($source_file);
    if (!$size || $size[2] < 1 || $size[2] > 3) {
        return '';
    }

    if (!is_dir($target_path)) {
        @mkdir($target_path, G5_DIR_PERMISSION, true);
        @chmod($target_path, G5_DIR_PERMISSION);
    }

    if (!is_dir($target_path) || !is_writable($target_path)) {
        return '';
    }

    // Animated GIF는 원본 유지
    if ($size[2] == 1 && is_animated_gif($source_file)) {
        return basename($source_file);
    }

    $ext = array(1 => 'gif', 2 => 'jpg', 3 => 'png');

    $thumb_filename = preg_replace("/\.[^\.]+$/i", "", $filename);
    $thumb_file = $target_path.'/thumb-'.$thumb_filename.'_'.$thumb_width.'x'.$thumb_height.'.'.$ext[$size[2]];

    $thumb_time = @filemtime($thumb_file);
    $source_time = @filemtime($source_file);

    if (is_file($thumb_file) && !$is_create && $source_time && $thumb_time && $source_time < $thumb_time) {
        return basename($thumb_file);
    }

    // 원본 이미지 생성
    $src = null;
    $degree = 0;
    $src_transparency = -1;

    if ($size[2] == 1) {
        $src = @imagecreatefromgif($source_file);
        if ($src !== false) {
            $src_transparency = @imagecolortransparent($src);
        }
    } else if ($size[2] == 2) {
        $src = @imagecreatefromjpeg($source_file);
        if ($src !== false && function_exists('exif_read_data')) {
            $exif = @exif_read_data($source_file);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 8: $degree = 90; break;
                    case 3: $degree = 180; break;
                    case 6: $degree = -90; break;
                }
                if ($degree) {
                    $rotated = @imagerotate($src, $degree, 0);
                    if ($rotated) {
                        $src = $rotated;
                        if ($degree == 90 || $degree == -90) {
                            $tmp = $size;
                            $size[0] = $tmp[1];
                            $size[1] = $tmp[0];
                        }
                    }
                }
            }
        }
    } else if ($size[2] == 3) {
        $src = @imagecreatefrompng($source_file);
        if ($src !== false) {
            @imagealphablending($src, true);
        }
    }

    if (!$src) {
        return '';
    }

    // 축소 비율 계산
    $is_large = true;
    if ($thumb_width > 0) {
        if ($thumb_height <= 0) {
            $thumb_height = (int)round(($thumb_width * $size[1]) / $size[0]);
        } else {
            if ($size[0] < $thumb_width || $size[1] < $thumb_height) {
                $is_large = false;
            }
        }
    } else if ($thumb_height > 0) {
        $thumb_width = (int)round(($thumb_height * $size[0]) / $size[1]);
    }

    $dst_x = 0;
    $dst_y = 0;
    $src_x = 0;
    $src_y = 0;
    $dst_w = $thumb_width;
    $dst_h = $thumb_height;
    $src_w = $size[0];
    $src_h = $size[1];

    $ratio = $dst_h / $dst_w;

    if ($is_large) {
        // 크롭
        if ($is_crop) {
            if ($crop_mode === 'center') {
                if ($size[1] / $size[0] >= $ratio) {
                    $src_h = (int)round($src_w * $ratio);
                    $src_y = (int)round(($size[1] - $src_h) / 2);
                } else {
                    $src_w = (int)round($size[1] / $ratio);
                    $src_x = (int)round(($size[0] - $src_w) / 2);
                }
            } else {
                if ($size[1] / $size[0] >= $ratio) {
                    $src_h = (int)round($src_w * $ratio);
                } else {
                    $src_w = (int)round($size[1] / $ratio);
                }
            }

            $dst = imagecreatetruecolor($dst_w, $dst_h);

            if ($size[2] == 3) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
            } else if ($size[2] == 1 && $src_transparency >= 0) {
                $palletsize = imagecolorstotal($src);
                if ($src_transparency < $palletsize) {
                    $transparent_color = imagecolorsforindex($src, $src_transparency);
                    $current_transparent = imagecolorallocate($dst, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
                    imagefill($dst, 0, 0, $current_transparent);
                    imagecolortransparent($dst, $current_transparent);
                }
            }
        } else {
            // 비율 유지
            $dst = imagecreatetruecolor($dst_w, $dst_h);
            $bgcolor = imagecolorallocate($dst, 255, 255, 255);

            if ($src_w > $src_h) {
                $tmp_h = (int)round(($dst_w * $src_h) / $src_w);
                $dst_y = (int)round(($dst_h - $tmp_h) / 2);
                $dst_h = $tmp_h;
            } else {
                $tmp_w = (int)round(($dst_h * $src_w) / $src_h);
                $dst_x = (int)round(($dst_w - $tmp_w) / 2);
                $dst_w = $tmp_w;
            }

            if ($size[2] == 3) {
                $bgcolor = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefill($dst, 0, 0, $bgcolor);
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
            } else if ($size[2] == 1 && $src_transparency >= 0) {
                $palletsize = imagecolorstotal($src);
                if ($src_transparency < $palletsize) {
                    $transparent_color = imagecolorsforindex($src, $src_transparency);
                    $current_transparent = imagecolorallocate($dst, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
                    imagefill($dst, 0, 0, $current_transparent);
                    imagecolortransparent($dst, $current_transparent);
                } else {
                    imagefill($dst, 0, 0, $bgcolor);
                }
            } else {
                imagefill($dst, 0, 0, $bgcolor);
            }
        }
    } else {
        // 원본이 더 작은 경우 패딩 처리
        $dst = imagecreatetruecolor($dst_w, $dst_h);
        $bgcolor = imagecolorallocate($dst, 255, 255, 255);

        if ($src_w < $dst_w) {
            if ($src_h >= $dst_h) {
                $dst_x = (int)round(($dst_w - $src_w) / 2);
                $src_h = $dst_h;
            } else {
                $dst_x = (int)round(($dst_w - $src_w) / 2);
                $dst_y = (int)round(($dst_h - $src_h) / 2);
                $dst_w = $src_w;
                $dst_h = $src_h;
            }
        } else if ($src_h < $dst_h) {
            $dst_y = (int)round(($dst_h - $src_h) / 2);
            $dst_h = $src_h;
            $src_w = $dst_w;
        }

        if ($size[2] == 3) {
            $bgcolor = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $bgcolor);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        } else if ($size[2] == 1 && $src_transparency >= 0) {
            $palletsize = imagecolorstotal($src);
            if ($src_transparency < $palletsize) {
                $transparent_color = imagecolorsforindex($src, $src_transparency);
                $current_transparent = imagecolorallocate($dst, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
                imagefill($dst, 0, 0, $current_transparent);
                imagecolortransparent($dst, $current_transparent);
            } else {
                imagefill($dst, 0, 0, $bgcolor);
            }
        } else {
            imagefill($dst, 0, 0, $bgcolor);
        }
    }

    imagecopyresampled($dst, $src, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

    if ($is_sharpen && $is_large) {
        $val = explode('/', $um_value);
        $amount = isset($val[0]) ? $val[0] : 80;
        $radius = isset($val[1]) ? $val[1] : 0.5;
        $threshold = isset($val[2]) ? $val[2] : 3;
        UnsharpMask($dst, $amount, $radius, $threshold);
    }

    if ($size[2] == 1) {
        imagegif($dst, $thumb_file);
    } else if ($size[2] == 3) {
        $png_compress = defined('G5_THUMB_PNG_COMPRESS') ? G5_THUMB_PNG_COMPRESS : 5;
        imagepng($dst, $thumb_file, $png_compress);
    } else {
        $jpg_quality = defined('G5_THUMB_JPG_QUALITY') ? G5_THUMB_JPG_QUALITY : 90;
        imagejpeg($dst, $thumb_file, $jpg_quality);
    }

    @chmod($thumb_file, G5_FILE_PERMISSION);

    imagedestroy($src);
    imagedestroy($dst);

    // CDN: 생성된 썸네일도 R2 미러링 (활성 시)
    if (function_exists('cdn_after_upload')) {
        cdn_after_upload($thumb_file);
    }

    return basename($thumb_file);
}

function UnsharpMask($img, $amount, $radius, $threshold)
{
    if ($amount > 500) $amount = 500;
    $amount = $amount * 0.016;
    if ($radius > 50) $radius = 50;
    $radius = $radius * 2;
    if ($threshold > 255) $threshold = 255;

    $radius = abs(round($radius));
    if ($radius === 0) {
        // 원본 유지
        return true;
    }

    $w = imagesx($img);
    $h = imagesy($img);
    $imgCanvas = imagecreatetruecolor($w, $h);
    $imgBlur = imagecreatetruecolor($w, $h);

    if (function_exists('imageconvolution')) {
        $matrix = array(
            array(1, 2, 1),
            array(2, 4, 2),
            array(1, 2, 1)
        );
        $divisor = array_sum(array_map('array_sum', $matrix));
        $offset = 0;

        imagecopy($imgBlur, $img, 0, 0, 0, 0, $w, $h);
        imageconvolution($imgBlur, $matrix, $divisor, $offset);
    } else {
        for ($i = 0; $i < $radius; $i++) {
            imagecopy($imgBlur, $img, 0, 0, 1, 0, $w - 1, $h);
            imagecopymerge($imgBlur, $img, 1, 0, 0, 0, $w, $h, 50);
            imagecopymerge($imgBlur, $img, 0, 0, 0, 0, $w, $h, 50);
            imagecopy($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h);

            imagecopymerge($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 33.33333);
            imagecopymerge($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 25);
        }
    }

    if ($threshold > 0) {
        for ($x = 0; $x < $w - 1; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgbOrig = imagecolorat($img, $x, $y);
                $rOrig = ($rgbOrig >> 16) & 0xFF;
                $gOrig = ($rgbOrig >> 8) & 0xFF;
                $bOrig = $rgbOrig & 0xFF;

                $rgbBlur = imagecolorat($imgBlur, $x, $y);
                $rBlur = ($rgbBlur >> 16) & 0xFF;
                $gBlur = ($rgbBlur >> 8) & 0xFF;
                $bBlur = $rgbBlur & 0xFF;

                $rNew = (abs($rOrig - $rBlur) >= $threshold)
                    ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig))
                    : $rOrig;
                $gNew = (abs($gOrig - $gBlur) >= $threshold)
                    ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig))
                    : $gOrig;
                $bNew = (abs($bOrig - $bBlur) >= $threshold)
                    ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig))
                    : $bOrig;

                if ($rOrig != $rNew || $gOrig != $gNew || $bOrig != $bNew) {
                    $pixCol = imagecolorallocate($img, $rNew, $gNew, $bNew);
                    imagesetpixel($img, $x, $y, $pixCol);
                }
            }
        }
    } else {
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgbOrig = imagecolorat($img, $x, $y);
                $rOrig = ($rgbOrig >> 16) & 0xFF;
                $gOrig = ($rgbOrig >> 8) & 0xFF;
                $bOrig = $rgbOrig & 0xFF;

                $rgbBlur = imagecolorat($imgBlur, $x, $y);
                $rBlur = ($rgbBlur >> 16) & 0xFF;
                $gBlur = ($rgbBlur >> 8) & 0xFF;
                $bBlur = $rgbBlur & 0xFF;

                $rNew = ($amount * ($rOrig - $rBlur)) + $rOrig;
                if ($rNew > 255) $rNew = 255;
                else if ($rNew < 0) $rNew = 0;

                $gNew = ($amount * ($gOrig - $gBlur)) + $gOrig;
                if ($gNew > 255) $gNew = 255;
                else if ($gNew < 0) $gNew = 0;

                $bNew = ($amount * ($bOrig - $bBlur)) + $bOrig;
                if ($bNew > 255) $bNew = 255;
                else if ($bNew < 0) $bNew = 0;

                $rgbNew = ($rNew << 16) + ($gNew << 8) + $bNew;
                imagesetpixel($img, $x, $y, $rgbNew);
            }
        }
    }

    imagedestroy($imgCanvas);
    imagedestroy($imgBlur);

    return true;
}

function is_animated_gif($filename)
{
    if (!($fh = @fopen($filename, 'rb'))) {
        return false;
    }

    $count = 0;

    while (!feof($fh) && $count < 2) {
        $chunk = fread($fh, 1024 * 100);
        if ($chunk === false || $chunk === '') {
            break;
        }
        $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches);
    }

    fclose($fh);
    return $count > 1;
}
?>
