<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 타임라인 원글 축약 스킨 (댓글 위에 표시)
 * 
 * 사용 가능한 변수:
 * - $wr_id: 글 ID
 * - $ch_id: 작성자 캐릭터 ID
 * - $ch_name: 작성자 캐릭터명 (이스케이프됨)
 * - $ch_image: 프로필 이미지 URL
 * - $content: 렌더링된 내용
 * - $content_raw: 원본 내용
 * - $time_ago: 시간 경과 문자열
 * - $profile_url: 프로필 페이지 URL
 * - $view_url: 상세보기 URL
 * - $images: 첨부 이미지 URL 배열
 * - $my_like: 좋아요 여부
 * - $my_bookmark: 북마크 여부
 * - $like_count: 좋아요 수
 * - $comment_count: 댓글 수
 * - $is_mine: 본인 글 여부
 */

// 내용 축약 (100자)
$short_content = mb_strlen(strip_tags($content_raw)) > 100 
    ? h(mb_substr(strip_tags($content_raw), 0, 100)).'...' 
    : $content;
$image_count = count($images);
$_origin_class = 'tl-item tl-item-origin';
if (function_exists('timeline_item_html_class')) {
    $_origin_class = timeline_item_html_class($_origin_class, isset($row) && is_array($row) ? $row : array());
}
?>
<div class="<?php echo $_origin_class; ?>" data-id="<?php echo $wr_id; ?>">
    <a href="<?php echo $profile_url; ?>" class="tl-profile" onclick="event.stopPropagation()">
        <img src="<?php echo $ch_image; ?>" alt="<?php echo $ch_name; ?>">
    </a>
    
    <div class="tl-body">
        <?php
        // item.content.skin.php용 변수 설정
        $content_type = 'origin';
        $item_id = $wr_id;
        $content = $short_content;
        $stop_propagation = true;
        include($timeline_skin_path.'/item.content.skin.php');
        ?>
    </div>
</div>
