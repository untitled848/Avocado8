<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 타임라인 댓글 쓰기 폼 스킨
 * MMB의 write_comment.php와 유사한 구조
 * 
 * 변수:
 * - $list_item: 원글 정보 (wr_id 등)
 * - $is_comment_write: 댓글 작성 권한 여부
 * - $w: 모드 (c: 새 댓글, cu: 수정)
 * - $character: 현재 로그인 캐릭터
 * - $timeline: 타임라인 설정
 */

if (!$is_comment_write) return;

// 댓글 모드 기본값
if ($w == '') $w = 'c';

// 캐릭터 정보
$my_ch_id = ses($character, 'ch_id', 0, 'int');
$ch_name = ses($character, 'ch_name', '', 'raw');

// 수정 모드일 때 기존 댓글 내용
$c_wr_content = '';
$c_id = ses($_GET, 'c_id', 0, 'int');
if ($w === 'cu' && $c_id) {
    $edit_comment = sql_fetch("SELECT wr_content FROM {$write_table} WHERE wr_id = '{$c_id}' AND wr_is_comment = 1");
    $c_wr_content = ses($edit_comment, 'wr_content', '');
}
?>
<!-- 타임라인 댓글 쓰기 시작 { -->
<aside class="tl-comment-write" id="tl_cw_<?php echo $list_item['wr_id']; ?>">
    <form class="tl-comment-form" data-parent="<?php echo $list_item['wr_id']; ?>" onsubmit="return submitComment(this);">
        <input type="hidden" name="w" value="<?php echo $w; ?>">
        <input type="hidden" name="tl_table" value="<?php echo $tl_table; ?>">
        <input type="hidden" name="wr_id" value="<?php echo $list_item['wr_id']; ?>">
        <?php if ($w === 'cu' && $c_id) { ?>
        <input type="hidden" name="comment_id" value="<?php echo $c_id; ?>">
        <?php } ?>

        <div class="tl-comment-input">
            <div class="tl-comment-avatar">
                <img src="<?php echo get_timeline_thumb($my_ch_id, 1); ?>" alt="<?php echo h($ch_name); ?>">
            </div>
            
            <div class="tl-comment-body">
                <textarea name="content" class="tl-comment-textarea" placeholder="답글을 입력하세요..." required><?php echo h($c_wr_content); ?></textarea>
                
                <div class="tl-comment-submit">
                    <button type="submit" class="tl-btn tl-btn-primary">
                        <?php echo ($w === 'cu') ? '수정' : '답글'; ?>
                    </button>
                </div>
            </div>
        </div>
    </form>
</aside>
<!-- } 타임라인 댓글 쓰기 끝 -->
