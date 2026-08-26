<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 안전한 기본값
$owner_front  = isset($owner_front)  ? $owner_front  : '';
$owner_behind = isset($owner_behind) ? $owner_behind : '';

for ($index = 0; $index < count($comment); $index++) {

    $log_comment = $comment[$index];
    if (!isset($log_comment['wr_id'])) {
        continue;
    }

    $comment_id = $log_comment['wr_id'];

    // 미디어 태그 치환 (사용 여부에 따라 유지)
    $content = isset($log_comment['content']) ? $log_comment['content'] : '';
    $content = preg_replace(
        "/\[\<a\s.*href\=\"(http|https|ftp|mms)\:\/\/([^[:space:]]+)\.(mp3|wma|wmv|asf|asx|mpg|mpeg)\".*\<\/a\>\]/i",
        "<script>doc_write(obj_movie('$1://$2.$3'));</script>",
        $content
    );

    // 삭제 가능 여부 기본값
    $is_delete = isset($log_comment['is_del']) ? (bool)$log_comment['is_del'] : false;

    // 액션 로그가 있고 관리자가 아니면 삭제 불가
    if (isset($log_comment['wr_log']) && $log_comment['wr_log'] && !$is_admin) {
        $is_delete = false;
    }

    // 수정/삭제/답변 링크용 기본값
    $c_reply_href = '';
    $c_edit_href  = '';

    // 조건 수정: 자신의 코멘트가 아닌 경우에만 컨트롤 허용
    if (
        isset($list_item['wr_id']) &&
        $list_item['wr_id'] != $log_comment['wr_id'] &&
        (
            (!empty($log_comment['is_reply'])) ||
            (!empty($log_comment['is_edit'])) ||
            (!empty($log_comment['is_del']))
        )
    ) {
        $query_string = '';
        if (isset($_SERVER['QUERY_STRING'])) {
            $query_string = str_replace("&", "&amp;", $_SERVER['QUERY_STRING']);
        }

        // 코멘트 수정 모드일 때 원본 내용 조회
        if ($w === 'cu') {
            $sql = " select wr_id, wr_content from {$write_table} where wr_id = '{$comment_id}' and wr_is_comment = '1' ";
            $cmt = sql_fetch($sql);
            $c_wr_content = isset($cmt['wr_content']) ? $cmt['wr_content'] : '';
        }

        $c_reply_href = './board.php?'.$query_string.'&amp;c_id='.$comment_id.'&amp;w=c#bo_vc_w_'.$list_item['wr_id'];
        $c_edit_href  = './board.php?'.$query_string.'&amp;c_id='.$comment_id.'&amp;w=cu#bo_vc_w_'.$list_item['wr_id'];
    }

    // 캐릭터 / 작성자 표시
    $is_comment_owner     = false;
    $comment_owner_front  = '';
    $comment_owner_behind = '';

    if (empty($log_comment['wr_noname'])) {

        // 관리자 아이콘
        if (
            isset($config['cf_admin'], $log_comment['mb_id']) &&
            $config['cf_admin'] === $log_comment['mb_id'] &&
            is_file(G5_DATA_PATH."/site/ico_admin")
        ) {
            $log_comment['ch_name'] = "<img src='".G5_DATA_URL."/site/ico_admin' alt='관리자' />";
        } else {
            // 캐릭터 정보
            $ch = array();
            if (isset($log_comment['ch_id'])) {
                $ch = get_character($log_comment['ch_id']);
            }

            if (isset($ch['ch_id']) && $ch['ch_id']) {
                $title_img = '';
                if (isset($log_comment['ti_id'])) {
                    $title_img = get_title_image($log_comment['ti_id']);
                }

                $ch_name = isset($ch['ch_name']) && $ch['ch_name'] ? $ch['ch_name'] : 'GUEST';

                $log_comment['ch_name'] = "
                    <a href='".G5_URL."/member/viewer.php?ch_id={$ch['ch_id']}' target='_blank'>
                        <i>".get_side_icon($ch['ch_side'])."</i>
                        ".$title_img."
                        ".$ch_name."
                    </a>";
            } else {
                $log_comment['ch_name'] = '';
            }
        }

        // 오너(회원) 이름
        if (!empty($log_comment['mb_id'])) {
            $wr_name = isset($log_comment['wr_name']) ? $log_comment['wr_name'] : '';
            $log_comment['name'] = "<a href='".G5_BBS_URL."/memo_form.php?me_recv_mb_id={$log_comment['mb_id']}' class='send_memo'>".$wr_name."</a>";
        } else {
            $log_comment['name'] = isset($log_comment['wr_name']) ? $log_comment['wr_name'] : '';
        }

        // 원글 작성자와 동일한 경우
        if (
            empty($list_item['wr_noname']) &&
            isset($list_item['mb_id'], $log_comment['mb_id']) &&
            $list_item['mb_id'] === $log_comment['mb_id']
        ) {
            $is_comment_owner     = true;
            $comment_owner_front  = $owner_front;
            $comment_owner_behind = $owner_behind;
        }
    } else {
        $is_comment_owner = false;
    }

    // wr_datetime 처리
    $wr_datetime = isset($log_comment['wr_datetime']) ? $log_comment['wr_datetime'] : '';
    $wr_datetime_ts = $wr_datetime ? strtotime($wr_datetime) : 0;
    ?>
    
    <div class="item-comment" id="c_<?php echo $comment_id ?>">
        <div class="co-header">
            <?php if (empty($log_comment['wr_noname'])) { ?>
            <p<?php echo $is_comment_owner ? ' class="owner"' : '';?>>
                <?php echo $comment_owner_front; ?>
                <strong><?php echo $log_comment['ch_name']; ?></strong>
                <span>[<?php echo $log_comment['name']; ?>]</span>
                <?php echo $comment_owner_behind; ?>
            </p>
            <?php } else { ?>
            <p>익명의 누군가</p>
            <?php } ?>
        </div>

        <div class="co-content">
            <div class="original_comment_area">
                <?php
                // row 단위 부가 렌더 (액션 로그 + 다이스 등 모든 모듈 통합).
                // module/_skin.php 가 제공하는 전역 함수.
                if (function_exists('bbs_render_row_extra')) {
                    $mmb_row_scope = (!empty($log_comment['wr_is_comment']) && (int)$log_comment['wr_is_comment'] === 1)
                        ? 'comment'
                        : 'post';
                    bbs_render_row_extra($log_comment, array(
                        'bo_table'        => isset($bo_table) ? $bo_table : '',
                        'board_skin_url'  => isset($board_skin_url) ? $board_skin_url : '',
                        'board_skin_path' => isset($board_skin_path) ? $board_skin_path : '',
                        'scope'           => $mmb_row_scope,
                    ));
                }

                // 링크
                if (!empty($log_comment['wr_link1']) || !empty($log_comment['wr_link2'])) {
                    ?>
                    <span class="link-box">
                        <?php if (!empty($log_comment['wr_link1'])) { ?>
                            <a href="<?php echo $log_comment['wr_link1']; ?>" target="_blank" class="link">LINK</a>
                        <?php } ?>
                        <?php if (!empty($log_comment['wr_link2'])) { ?>
                            <a href="<?php echo $log_comment['wr_link2']; ?>" target="_blank" class="link">LINK</a>
                        <?php } ?>
                    </span>
                    <?php
                }

                // 코멘트 내용 처리
                $log_comment_content = isset($log_comment['content']) ? $log_comment['content'] : '';
                $log_comment_content = autolink($log_comment_content, $bo_table, $stx);
                $log_comment_content = emote_ev($log_comment_content);
                echo $log_comment_content;
                ?>
            </div>

            <?php if (!empty($log_comment['is_edit'])) { ?>
            <div class="modify_area" id="save_comment_<?php echo $comment_id ?>">
                <textarea id="save_co_comment_<?php echo $comment_id ?>"><?php echo get_text(isset($log_comment['content1']) ? $log_comment['content1'] : '', 0); ?></textarea>
                <button type="button" class="mod_comment ui-btn" onclick="modify_commnet('<?php echo $comment_id ?>'); return false;">수정</button>
            </div>
            <?php if (!empty($GLOBALS['mmb_climit_text']) && (int)$GLOBALS['mmb_climit_text'] > 0) { ?>
            <script>(function(){
                var ta = document.getElementById('save_co_comment_<?php echo (int)$comment_id; ?>');
                if (ta && window.mmbBindCommentLimit) window.mmbBindCommentLimit(ta, <?php echo (int)$GLOBALS['mmb_climit_text']; ?>, <?php echo json_encode($GLOBALS['mmb_climit_excs'] ?? array(), JSON_UNESCAPED_UNICODE); ?>);
            })();</script>
            <?php } ?>
            <?php } ?>
        </div>

        <div class="co-footer">
            <span class="date">
                <?php echo $wr_datetime_ts ? date('Y-m-d H:i:s', $wr_datetime_ts) : ''; ?>
            </span>
            <?php if ($is_delete && !empty($log_comment['del_link'])) { ?>
                <a href="<?php echo $log_comment['del_link']; ?>" onclick="return comment_delete();" class="del">삭제</a>
            <?php } ?>
            <?php if (!empty($log_comment['is_edit']) && $c_edit_href) { ?>
                <a href="<?php echo $c_edit_href; ?>" onclick="comment_box('<?php echo $comment_id ?>', '<?php echo $list_item['wr_id']; ?>'); return false;" class="mod">수정</a>
            <?php } ?>
        </div>
    </div>
<?php
} // end for
?>
