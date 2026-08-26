<?php
if (!defined('_GNUBOARD_')) exit; // 媛쒕퀎 ?섏씠吏 ?묎렐 遺덇?

?><script>
// 글자수 제한
var char_min = parseInt(<?php echo $comment_min; ?>); // 최소
var char_max = parseInt(<?php echo $comment_max; ?>); // 최대
</script>

<!-- 댓글 시작 { -->
<section id="bo_vc">
    <?php
    $cmt_amt = count($list);

    for ($i = 0; $i < $cmt_amt; $i++) {

        $row = $list[$i];

        $comment_id = isset($row['wr_id']) ? $row['wr_id'] : '';
        if ($comment_id === '') {
            continue;
        }

        // 댓글 단계
        $cmt_depth = isset($row['wr_comment_reply']) ? strlen($row['wr_comment_reply']) * 20 : 0;

        // 내용
        $comment = isset($row['content']) ? $row['content'] : '';

        // 미디어 링크 치환
        $comment = preg_replace(
            "/\[\<a\s.*href\=\"(http|https|ftp|mms)\:\/\/([^[:space:]]+)\.(mp3|wma|wmv|asf|asx|mpg|mpeg)\".*\<\/a\>\]/i",
            "<script>doc_write(obj_movie('$1://$2.$3'));</script>",
            $comment
        );

        // z-index 계산
        $cmt_sv = $cmt_amt - $i + 1;
        ?>
        <article id="c_<?php echo $comment_id; ?>"
            <?php if ($cmt_depth > 0) { ?>
                style="margin-left:<?php echo $cmt_depth; ?>px;border-top-color:rgba(255, 255, 255, .2)"
            <?php } ?>
        >
            <header style="z-index:<?php echo $cmt_sv; ?>">
                <?php
                // 작성자명
                echo isset($row['name']) ? $row['name'] : '';

                // 대댓글 아이콘
                if ($cmt_depth > 0) {
                    ?>
                    <img src="<?php echo $board_skin_url; ?>/img/icon_reply.gif" class="icon_reply" alt="댓글의 댓글">
                    <?php
                }

                // IP 출력
                if ($is_ip_view && isset($row['ip']) && $row['ip'] !== '') {
                    ?>
                    <span class="bo_vc_hdinfo"><?php echo $row['ip']; ?></span>
                    <?php
                }

                // 날짜 출력
                if (isset($row['datetime']) && $row['datetime'] !== '') {
                    $dt_iso = date('Y-m-d\TH:i:s+09:00', strtotime($row['datetime']));
                    $dt_txt = date('m/d H:i', strtotime($row['datetime']));
                    ?>
                    <span class="bo_vc_hdinfo">
                        <time datetime="<?php echo $dt_iso; ?>"><?php echo $dt_txt; ?></time>
                    </span>
                    <?php
                }
                ?>
            </header>

     
            <?php
            // 모듈 부가 렌더 (액션 로그, 다이스 등)
            if (function_exists('bbs_render_row_extra')) {
                bbs_render_row_extra($row, array(
                    'bo_table'        => isset($bo_table) ? $bo_table : '',
                    'board_skin_url'  => isset($board_skin_url) ? $board_skin_url : '',
                    'board_skin_path' => isset($board_skin_path) ? $board_skin_path : '',
                    'scope'           => 'comment',
                ));
            }
            ?>
            <!-- 댓글 출력 -->
            <p>
                <?php if (isset($row['wr_option']) && strstr($row['wr_option'], 'secret')) { ?>
                    <img src="<?php echo $board_skin_url; ?>/img/icon_secret.gif" alt="비밀글">
                <?php } ?>
                <?php echo $comment; ?>
            </p>
            
            <span id="edit_<?php echo $comment_id; ?>"></span><!-- 수정 -->
            <span id="reply_<?php echo $comment_id; ?>"></span><!-- 답변 -->

            <input type="hidden"
                   value="<?php echo (isset($row['wr_option']) && strstr($row['wr_option'], 'secret')) ? 'secret' : ''; ?>"
                   id="secret_comment_<?php echo $comment_id; ?>">

            <textarea id="save_comment_<?php echo $comment_id; ?>" style="display:none"><?php
                echo isset($row['content1']) ? get_text($row['content1'], 0) : '';
            ?></textarea>

            <?php
            $has_reply = !empty($row['is_reply']);
            $has_edit  = !empty($row['is_edit']);
            $has_del   = !empty($row['is_del']);

            if ($has_reply || $has_edit || $has_del) {

                $query_string = isset($_SERVER['QUERY_STRING'])
                    ? clean_query_string($_SERVER['QUERY_STRING'])
                    : '';

                // 댓글 수정 내용 로드
                if ($w === 'cu' && $c_id && $write_table) {
                    $sql = " select wr_id, wr_content, mb_id
                             from {$write_table}
                             where wr_id = '{$c_id}'
                               and wr_is_comment = '1' ";
                    $cmt = sql_fetch($sql);

                    if (!($is_admin || (isset($member['mb_id'], $cmt['mb_id']) && $member['mb_id'] === $cmt['mb_id'] && $cmt['mb_id']))) {
                        $cmt['wr_content'] = '';
                    }

                    $c_wr_content = isset($cmt['wr_content']) ? $cmt['wr_content'] : '';
                }

                $c_reply_href = './board.php?'.$query_string.'&amp;c_id='.$comment_id.'&amp;w=c#bo_vc_w';
                $c_edit_href  = './board.php?'.$query_string.'&amp;c_id='.$comment_id.'&amp;w=cu#bo_vc_w';
                ?>
                <footer>
                    <ul class="bo_vc_act">
                        <?php if ($has_reply) { ?>
                            <li>
                                <a href="<?php echo $c_reply_href; ?>"
                                   onclick="comment_box('<?php echo $comment_id; ?>', 'c'); return false;">답변</a>
                            </li>
                        <?php } ?>
                        <?php if ($has_edit) { ?>
                            <li>
                                <a href="<?php echo $c_edit_href; ?>"
                                   onclick="comment_box('<?php echo $comment_id; ?>', 'cu'); return false;">수정</a>
                            </li>
                        <?php } ?>
                        <?php if ($has_del && isset($row['del_link'])) { ?>
                            <li>
                                <a href="<?php echo $row['del_link']; ?>"
                                   onclick="return comment_delete();">삭제</a>
                            </li>
                        <?php } ?>
                    </ul>
                </footer>
                <?php
            }
            ?>
        </article>
    <?php } // end for ?>

    <?php if ($cmt_amt === 0) { ?>
        <p id="bo_vc_empty">등록된 댓글이 없습니다.</p>
    <?php } ?>

</section>
<!-- } 댓글 끝 -->

<?php if ($is_comment_write) {

    if ($w === '') {
        $w = 'c';
    }
    ?>
    <!-- 댓글 쓰기 시작 { -->
    <aside id="bo_vc_w">
        <form name="fviewcomment" action="<?php echo $comment_action_url; ?>"
              onsubmit="return fviewcomment_submit(this);" method="post" autocomplete="off">
            <input type="hidden" name="w" value="<?php echo $w; ?>" id="w">
            <input type="hidden" name="bo_table" value="<?php echo $bo_table; ?>">
            <input type="hidden" name="wr_id" value="<?php echo $wr_id; ?>">
            <input type="hidden" name="comment_id" value="<?php echo $c_id; ?>" id="comment_id">
            <input type="hidden" name="sca" value="<?php echo $sca; ?>">
            <input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
            <input type="hidden" name="stx" value="<?php echo $stx; ?>">
            <input type="hidden" name="spt" value="<?php echo $spt; ?>">
            <input type="hidden" name="page" value="<?php echo $page; ?>">
            <input type="hidden" name="is_good" value="">

            <div class="tbl_frm01 tbl_wrap">
                <table class="theme-form">
                    <colgroup>
                        <col style="width:80px;">
                        <col>
                    </colgroup>
                    <tbody>
                    <?php if ($is_guest) { ?>
                        <tr>
                            <th scope="row">
                                <label for="wr_name">이름<strong class="sound_only"> 필수</strong></label>
                            </th>
                            <td>
                                <input type="text" name="wr_name"
                                       value="<?php echo get_cookie('ck_sns_name'); ?>"
                                       id="wr_name"
                                       required
                                       class="frm_input required"
                                       size="5"
                                       maxlength="20">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="wr_password">비밀번호<strong class="sound_only"> 필수</strong></label>
                            </th>
                            <td>
                                <input type="password" name="wr_password" id="wr_password"
                                       required
                                       class="frm_input required"
                                       size="10"
                                       maxlength="20">
                            </td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <th scope="row"><label for="wr_secret">비밀글</label></th>
                        <td>
                            <input type="checkbox" name="wr_secret" value="secret" id="wr_secret">
                        </td>
                    </tr>
                    <?php if ($module_comment_options !== '') { ?>
                    <tr>
                        <th scope="row">옵션</th>
                        <td><?php echo $module_comment_options; ?></td>
                    </tr>
                    <?php } ?>
                    <?php if (!empty($module_comment_after_action)) { ?>
                    <tr>
                        <th scope="row">아이템</th>
                        <td><?php echo $module_comment_after_action; ?></td>
                    </tr>
                    <?php } ?>
                    <?php if ($module_comment_action_options !== '') { ?>
                    <tr>
                        <th scope="row">액션</th>
                        <td>
                            <input type="radio" name="action" id="bvc_action_default" value="" checked>
                            <label for="bvc_action_default">일반&nbsp;&nbsp;</label>
                            <?php echo $module_comment_action_options; ?>
                            <?php if ($module_comment_extra !== '') echo $module_comment_extra; ?>
                        </td>
                    </tr>
                    <?php } ?>
                    <tr>
                        <th scope="row">내용</th>
                        <td>
                            <?php if ($comment_min || $comment_max) { ?>
                                <strong id="char_cnt">
                                    <span id="char_count"></span>글자
                                </strong>
                            <?php } ?>
                            <textarea id="wr_content" name="wr_content"
                                      maxlength="10000"
                                      required
                                      class="required"
                                      title="내용"
                                      <?php if ($comment_min || $comment_max) { ?>
                                      onkeyup="check_byte('wr_content', 'char_count');"
                                      <?php } ?>
                            ><?php echo $c_wr_content; ?></textarea>

                            <?php if ($comment_min || $comment_max) { ?>
                                <script>check_byte('wr_content', 'char_count');</script>
                            <?php } ?>

                            <script>
                            $(document).on("keyup change", "textarea#wr_content[maxlength]", function() {
                                var str = $(this).val();
                                var mx = parseInt($(this).attr("maxlength"), 10);
                                if (str.length > mx) {
                                    $(this).val(str.substr(0, mx));
                                    return false;
                                }
                            });
                            </script>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="btn_confirm">
                <button type="submit" id="btn_submit" class="ui-btn point">댓글등록</button>
            </div>
            
        </form>
    </aside>

    <script>
    var save_before = '';
    var save_html   = document.getElementById('bo_vc_w').innerHTML;

    function good_and_write()
    {
        var f = document.fviewcomment;
        if (fviewcomment_submit(f)) {
            f.is_good.value = 1;
            f.submit();
        } else {
            f.is_good.value = 0;
        }
    }

    function fviewcomment_submit(f)
    {
        var pattern = /(^\s*)|(\s*$)/g;

        f.is_good.value = 0;

        var subject = "";
        var content = "";

        $.ajax({
            url: g5_bbs_url + "/ajax.filter.php",
            type: "POST",
            data: {
                "subject": "",
                "content": f.wr_content.value
            },
            dataType: "json",
            async: false,
            cache: false,
            success: function(data) {
                subject = data.subject;
                content = data.content;
            }
        });

        if (content) {
            alert("내용에 금지단어('" + content + "')가 포함되어있습니다");
            f.wr_content.focus();
            return false;
        }

        // 내용 공백 제거
        var el = document.getElementById('wr_content');
        el.value = el.value.replace(pattern, "");

        if (char_min > 0 || char_max > 0) {
            check_byte('wr_content', 'char_count');
            var cnt = parseInt(document.getElementById('char_count').innerHTML, 10);

            if (char_min > 0 && cnt < char_min) {
                alert("댓글은 " + char_min + "글자 이상 쓰셔야 합니다.");
                return false;
            }

            if (char_max > 0 && cnt > char_max) {
                alert("댓글은 " + char_max + "글자 이하로 쓰셔야 합니다.");
                return false;
            }
        } else if (!el.value) {
            alert("댓글을 입력하여 주십시오.");
            return false;
        }

        if (typeof f.wr_name !== "undefined") {
            f.wr_name.value = f.wr_name.value.replace(pattern, "");
            if (!f.wr_name.value) {
                alert("이름이 입력되지 않았습니다.");
                f.wr_name.focus();
                return false;
            }
        }

        if (typeof f.wr_password !== "undefined") {
            f.wr_password.value = f.wr_password.value.replace(pattern, "");
            if (!f.wr_password.value) {
                alert("비밀번호가 입력되지 않았습니다.");
                f.wr_password.focus();
                return false;
            }
        }

        set_comment_token(f);

        document.getElementById("btn_submit").disabled = true;

        return true;
    }

    function comment_box(comment_id, work)
    {
        var el_id;

        if (comment_id) {
            if (work === 'c')
                el_id = 'reply_' + comment_id;
            else
                el_id = 'edit_' + comment_id;
        } else {
            el_id = 'bo_vc_w';
        }

        if (save_before !== el_id) {

            if (save_before) {
                var before_el = document.getElementById(save_before);
                if (before_el) {
                    before_el.style.display = 'none';
                    before_el.innerHTML = '';
                }
            }

            var el = document.getElementById(el_id);
            if (!el) return;

            el.style.display = '';
            el.innerHTML = save_html;

            if (work === 'cu' && comment_id) {
                var saved = document.getElementById('save_comment_' + comment_id);
                if (saved) {
                    document.getElementById('wr_content').value = saved.value;
                }

                if (typeof char_count !== "undefined") {
                    check_byte('wr_content', 'char_count');
                }

                var sec = document.getElementById('secret_comment_' + comment_id);
                if (sec && sec.value) {
                    document.getElementById('wr_secret').checked = true;
                } else {
                    document.getElementById('wr_secret').checked = false;
                }
            }

            document.getElementById('comment_id').value = comment_id;
            document.getElementById('w').value = work;

            save_before = el_id;
        }
    }

    function comment_delete()
    {
        return confirm("이 댓글을 삭제하시겠습니까?");
    }

    // 기본 폼 노출
    comment_box('', 'c');
    </script>
<?php } ?>
<!-- } 댓글 쓰기 끝 -->
