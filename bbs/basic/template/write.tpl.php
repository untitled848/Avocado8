<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/**
 * basic 글쓰기 폼 — 템플릿 (HTML 출력 전용)
 *
 * 진입 변수 (controller 에서 set):
 *   $option, $option_hidden, $module_options, $is_error, ...
 *   $bo_table, $w, $wr_id, $sca, $sfl, $stx, $spt, $sst, $sod, $page
 *   $action_url, $is_notice, $is_html, $is_secret, $is_mail, $is_name, $is_password
 *   $is_category, $category_option, $is_file, $is_file_content, $file_count, $file
 *   $write_min, $write_max, $editor_html, $editor_js
 *   $name, $password_required, $subject, $upload_max_filesize, $board
 */
?>

<div class="frame-theme-BODY">
	<div class="inner">

		<?php if ($board['bo_content_head']) { ?>
			<div class="board-notice">
				<?php echo stripslashes($board['bo_content_head']); ?>
			</div>
		<?php } ?>

<section id="bo_w">
    <!-- 게시물 작성/수정 시작 { -->
    <form name="fwrite" id="fwrite" action="<?php echo $action_url ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
    <input type="hidden" name="uid" value="<?php echo get_uniqid(); ?>">
    <input type="hidden" name="w" value="<?php echo $w ?>">
    <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
    <input type="hidden" name="wr_id" value="<?php echo $wr_id ?>">
    <input type="hidden" name="sca" value="<?php echo $sca ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
    <input type="hidden" name="stx" value="<?php echo $stx ?>">
    <input type="hidden" name="spt" value="<?php echo $spt ?>">
    <input type="hidden" name="sst" value="<?php echo $sst ?>">
    <input type="hidden" name="sod" value="<?php echo $sod ?>">
    <input type="hidden" name="page" value="<?php echo $page ?>">
    <?php echo $option_hidden; ?>

	<table class="theme-form">
		<colgroup>
			<col style="width: 100px;" />
			<col />
		</colgroup>
        <tbody>
        <?php if ($is_name) { ?>
        <tr>
            <th scope="row"><label for="wr_name">이름<strong class="sound_only">필수</strong></label></th>
            <td><input type="text" name="wr_name" value="<?php echo $name ?>" id="wr_name" required class="frm_input required" size="10" maxlength="20"></td>
        </tr>
        <?php } ?>

        <?php if ($is_password) { ?>
        <tr>
            <th scope="row"><label for="wr_password">비밀번호<strong class="sound_only">필수</strong></label></th>
            <td><input type="password" name="wr_password" id="wr_password" <?php echo $password_required ?> class="frm_input <?php echo $password_required ?>" maxlength="20"></td>
        </tr>
        <?php } ?>

        <?php if ($option) { ?>
        <tr>
            <th scope="row">옵션</th>
            <td><?php echo $option ?></td>
        </tr>
        <?php } ?>

        <?php if ($module_action_options !== '') { ?>
        <tr>
            <th scope="row"><label for="action">액션</label></th>
            <td>
                <select name="action" id="action">
                    <option value="">일반</option>
                    <?php echo $module_action_options; ?>
                </select>
                <?php if ($module_action_panels !== '') { ?>
                <div class="bo_w_action_panels"><?php echo $module_action_panels; ?></div>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>
        <?php if ($module_options !== '') { ?>
        <tr>
            <th scope="row">옵션</th>
            <td><?php echo $module_options ?></td>
        </tr>
        <?php } ?>

        <?php if ($is_category) { ?>
        <tr>
            <th scope="row"><label for="ca_name">분류<strong class="sound_only">필수</strong></label></th>
            <td>
                <select name="ca_name" id="ca_name" required class="required" >
                    <option value="">선택하세요</option>
                    <?php echo $category_option ?>
                </select>
            </td>
        </tr>
        <?php } ?>

        <tr>
            <th scope="row"><label for="wr_subject">제목<strong class="sound_only">필수</strong></label></th>
            <td>
                <div id="autosave_wrapper">
                    <input type="text" name="wr_subject" value="<?php echo $subject ?>" id="wr_subject" required class="frm_input required" size="50" maxlength="255">
                </div>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="wr_content">내용<strong class="sound_only">필수</strong></label></th>
            <td class="wr_content">
                <?php if ($write_min || $write_max) { ?>
                <p id="char_count_desc">이 게시판은 최소 <strong><?php echo $write_min; ?></strong>글자 이상, 최대 <strong><?php echo $write_max; ?></strong>글자 이하까지 글을 쓰실 수 있습니다.</p>
                <?php } ?>
                <?php echo $editor_html; ?>
                <?php if ($write_min || $write_max) { ?>
                <div id="char_count_wrap"><span id="char_count"></span>글자</div>
                <?php } ?>
            </td>
        </tr>

        <?php for ($i = 0; $is_file && $i < $file_count; $i++) { ?>
        <tr>
            <th scope="row">파일 #<?php echo $i + 1 ?></th>
            <td>
                <input type="file" name="bf_file[]" title="파일첨부 <?php echo $i + 1 ?> : 용량 <?php echo $upload_max_filesize ?> 이하만 업로드 가능" class="frm_file frm_input">
                <?php if ($is_file_content) { ?>
                <input type="text" name="bf_content[]" value="<?php echo ($w == 'u') ? $file[$i]['bf_content'] : ''; ?>" title="파일 설명을 입력해주세요." class="frm_file frm_input" size="50">
                <?php } ?>
                <?php if ($w == 'u' && $file[$i]['file']) { ?>
                <input type="checkbox" id="bf_file_del<?php echo $i ?>" name="bf_file_del[<?php echo $i; ?>]" value="1"> <label for="bf_file_del<?php echo $i ?>"><?php echo $file[$i]['source'].'('.$file[$i]['size'].')'; ?> 파일 삭제</label>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>

        </tbody>
	</table>

    <div class="btn_confirm">
        <button type="submit" id="btn_submit" accesskey="s" class="ui-btn point">작성</button>
        <a href="./board.php?bo_table=<?php echo $bo_table ?>" class="ui-btn">목록</a>
    </div>
    </form>

    <script>
    <?php if ($write_min || $write_max) { ?>
    var char_min = parseInt(<?php echo $write_min; ?>);
    var char_max = parseInt(<?php echo $write_max; ?>);
    check_byte("wr_content", "char_count");

    $(function() {
        $("#wr_content").on("keyup", function() {
            check_byte("wr_content", "char_count");
        });
    });
    <?php } ?>

    function html_auto_br(obj)
    {
        if (obj.checked) {
            result = confirm("자동 줄바꿈을 하시겠습니까?\n\n자동 줄바꿈은 게시물 내용중 줄바뀐 곳을<br>태그로 변환하는 기능입니다.");
            if (result)
                obj.value = "html2";
            else
                obj.value = "html1";
        } else {
            obj.value = "";
        }
    }

    function fwrite_submit(f)
    {
        <?php echo $editor_js; ?>

        var subject = "";
        var content = "";
        $.ajax({
            url: g5_bbs_url + "/ajax.filter.php",
            type: "POST",
            data: {
                "subject": f.wr_subject.value,
                "content": f.wr_content.value
            },
            dataType: "json",
            async: false,
            cache: false,
            success: function(data, textStatus) {
                subject = data.subject;
                content = data.content;
            }
        });

        if (subject) {
            alert("제목에 금지단어('" + subject + "')가 포함되어있습니다");
            f.wr_subject.focus();
            return false;
        }

        if (content) {
            alert("내용에 금지단어('" + content + "')가 포함되어있습니다");
            if (typeof(ed_wr_content) != "undefined")
                ed_wr_content.returnFalse();
            else
                f.wr_content.focus();
            return false;
        }

        if (document.getElementById("char_count")) {
            if (char_min > 0 || char_max > 0) {
                var cnt = parseInt(check_byte("wr_content", "char_count"));
                if (char_min > 0 && char_min > cnt) {
                    alert("내용은 " + char_min + "글자 이상 쓰셔야 합니다.");
                    return false;
                } else if (char_max > 0 && char_max < cnt) {
                    alert("내용은 " + char_max + "글자 이하로 쓰셔야 합니다.");
                    return false;
                }
            }
        }

        document.getElementById("btn_submit").disabled = "disabled";

        return true;
    }
    </script>

    <script>
    // 액션 드롭다운: 선택값에 해당하는 .comment-data 패널만 노출
    $('#action').on('change', function() {
        var view_idx = $(this).val();
        $('.bo_w_action_panels .comment-data').removeClass('on');
        if (view_idx) $('#action_' + view_idx).addClass('on');
    });
    </script>
</section>
<!-- } 게시물 작성/수정 끝 -->
	<br /><br /><br />
	</div>
