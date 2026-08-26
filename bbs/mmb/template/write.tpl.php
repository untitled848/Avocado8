<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/**
 * mmb 글쓰기 폼 — 템플릿 (HTML / JS)
 *
 * 컨트롤러(controller/write.controller.php)에서 준비된 변수만 사용한다.
 * 도메인 로직(쿼리/조건 분기)은 이 파일에 추가하지 말 것.
 */
?>

<div id="load_log_board">
    <section id="bo_w" class="mmb-board">
        <!-- 게시물 작성/수정 시작 { -->
        <form name="fwrite" id="fwrite" action="<?php echo $action_url; ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="uid" value="<?php echo get_uniqid(); ?>">
            <input type="hidden" name="w" value="<?php echo $w; ?>">
            <input type="hidden" name="bo_table" value="<?php echo $bo_table; ?>">
            <input type="hidden" name="wr_id" value="<?php echo $wr_id; ?>">
            <input type="hidden" name="sca" value="<?php echo $sca; ?>">
            <input type="hidden" name="sfl" value="<?php echo $sfl; ?>">
            <input type="hidden" name="stx" value="<?php echo $stx; ?>">
            <input type="hidden" name="spt" value="<?php echo $spt; ?>">
            <input type="hidden" name="sst" value="<?php echo $sst; ?>">
            <input type="hidden" name="sod" value="<?php echo $sod; ?>">
            <input type="hidden" name="page" value="<?php echo (int)$page; ?>">
            <input type="hidden" name="wr_subject" value="<?php echo $write['wr_subject']; ?>">
            <input type="hidden" name="wr_width" id="wr_width" value="<?php echo $write['wr_width']; ?>">
            <input type="hidden" name="wr_height" id="wr_height" value="<?php echo $write['wr_height']; ?>">
            <?php echo $option_hidden; ?>

            <!-- LOG 등록 부분 -->
            <div id="view_image" class="theme-box">
                <span><?php echo $img_data; ?></span>
                <em id="view_image_loading">...LOADING...</em>
                <?php if ($image_url) { ?>
                <img src="<?php echo $image_url; ?>" id="prev_view_image">
                <?php } ?>
            </div>

            <dl>
                <dt>
                    <select name="wr_type" onchange="fn_log_type(this.value);">
                        <option value="UPLOAD"<?php echo ($write['wr_type'] === 'UPLOAD' ? ' selected' : ''); ?>>UPLOAD</option>
                        <option value="URL"<?php echo ($write['wr_type'] === 'URL' ? ' selected' : ''); ?>>URL</option>
                        <?php if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('bbs_write_form_wr_type_options', '', $mmb_form_ctx); ?>
                    </select>
                </dt>
                <dd>
                    <div id="add_UPLOAD"<?php echo ($write['wr_type'] === 'URL' ? " style='display:none;'" : ""); ?>>
                        <input type="file" id="wr_file" name="bf_file[]" title="로그등록 :  용량 <?php echo $upload_max_filesize; ?> 이하만 업로드 가능" class="frm_file frm_input view_image_area">
                        <?php if ($w == 'u' && isset($file[0]['file']) && $file[0]['file']) { ?>
                            <input type="checkbox" id="bf_file_del0" name="bf_file_del[0]" value="1">
                            <label for="bf_file_del0">
                                <?php
                                $src = isset($file[0]['source']) ? $file[0]['source'] : '';
                                $size = isset($file[0]['size']) ? $file[0]['size'] : '';
                                echo $src.'('.$size.')';
                                ?> 로그 삭제
                            </label>
                        <?php } ?>
                    </div>
                    <div id="add_URL"<?php echo ($write['wr_type'] !== 'URL' ? " style='display:none;'" : ""); ?>>
                        <input type="text" name="wr_url" value="<?php echo $write['wr_url']; ?>" title="이미지 링크를 가져와 주시길 바랍니다." id="wr_url" class="frm_input view_image_area" placeholder="이미지 링크 입력">
                    </div>
                    <?php if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('bbs_write_form_wr_type_panels', '', $mmb_form_ctx); ?>
                </dd>
            </dl>

            <?php if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('bbs_write_form_before_action', '', $mmb_form_ctx); ?>
            
            <div class="theme-box">
            <?php if ($is_category) { ?>
                <ul id="board_category">
                    <?php echo $category_option; ?>
                </ul>
            <?php } ?>

            <?php if (!isset($write['wr_log']) || !$write['wr_log']) { ?>
            <?php if ($character['ch_state'] === '승인') { ?>
                <div id="board_action" class="inner">
                    <dl>
                        <dt><label for="action"><i class="icon act"></i>Action</label></dt>
                        <dd>
                            <select name="action" id="action">
                                <option value="">일반행동</option>
                                <?php if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('bbs_write_form_action_options', '', $mmb_form_ctx); ?>
                            </select>
                        </dd>
                    </dl>

                    <?php if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('bbs_write_form_action_panels', '', $mmb_form_ctx); ?>
				</div>
               
            <?php } ?>
            <?php } ?>
				<?php if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('bbs_write_form_after_action', '', $mmb_form_ctx); ?>
                <div class="inner">

                    <dl>
                        <dt><i class="icon gear"></i>Option</dt>
                        <dd>
                            <?php if ($is_member) { ?>
                            <fieldset>
                                <input type="checkbox" id="wr_secret" name="wr_secret" value="1"<?php echo ($write['wr_secret'] ? ' checked' : ''); ?>>
                                <label for="wr_secret">멤버공개</label>
                            </fieldset>
                            <fieldset>
                                <input type="checkbox" id="wr_adult" name="wr_adult" value="1"<?php echo ($write['wr_adult'] ? ' checked' : ''); ?>>
                                <label for="wr_adult">19금</label>
                            </fieldset>
                            <?php } ?>

                            <fieldset>
                                <input type="checkbox" id="wr_wide" name="wr_wide" value="1"<?php echo ($write['wr_wide'] ? ' checked' : ''); ?>>
                                <label for="wr_wide">리플창 아래로</label>
                            </fieldset>
                            <fieldset>
                                <input type="checkbox" id="wr_plip" name="wr_plip" value="1"<?php echo ($write['wr_plip'] ? ' checked' : ''); ?>>
                                <label for="wr_plip">로그접기 (<?php echo (int)$board['bo_gallery_height']; ?>px 이상은 자동으로 접힙니다.)</label>
                            </fieldset>

                            <?php if ($board['bo_use_noname'] && $is_member) { ?>
                            <fieldset>
                                <input type="checkbox" id="wr_noname" name="wr_noname" value="1"<?php echo ($write['wr_noname'] ? ' checked' : ''); ?>>
                                <label for="wr_noname">익명</label>
                            </fieldset>
                            <?php } ?>
							<?php if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('bbs_write_form_options', '', $mmb_form_ctx); ?>
                        </dd>
                    </dl>

                    <?php if ($is_name) { ?>
                    <dl>
                        <dt><label for="wr_name">이름</label></dt>
                        <dd>
                            <input type="text" name="wr_name" value="<?php echo $name; ?>" id="wr_name" required class="frm_input required" size="10" maxlength="20">
                        </dd>
                    </dl>
                    <?php } ?>

                    <?php if ($is_password) { ?>
                    <dl>
                        <dt><label for="wr_password">비밀번호</label></dt>
                        <dd>
                            <input type="password" name="wr_password" id="wr_password" value="<?php echo $_COOKIE['MMB_PW']; ?>" class="frm_input" maxlength="20">
                        </dd>
                    </dl>
                    <?php } ?>

                    <?php for ($i=1; $is_link && $i<=G5_LINK_COUNT; $i++) { ?>
                    <dl>
                        <dt>
                            <label for="wr_link<?php echo $i; ?>"><i class="icon link"></i>Link #<?php echo $i; ?></label>
                        </dt>
                        <dd>
                            <input type="text" name="wr_link<?php echo $i; ?>" value="<?php echo ($w == 'u' && isset($write['wr_link'.$i]) ? $write['wr_link'.$i] : ''); ?>" id="wr_link<?php echo $i; ?>" class="frm_input" size="50">
                        </dd>
                    </dl>
                    <?php } ?>

                </div>
            </div>

            <hr class="padding small">

            <div class="comments">
                <?php if ($write_min || $write_max) { ?>
                <p id="char_count_desc">
                    이 게시판은 최소 <strong><?php echo $write_min; ?></strong>글자 이상,
                    최대 <strong><?php echo $write_max; ?></strong>글자 이하까지 글을 쓰실 수 있습니다.
                </p>
                <?php } ?>

                <?php echo $editor_html; ?>

                <?php
                // avo8.0: 본문 글자 수 제한 (bo_write_limit_text)
                $_climit_text_pp = (int)($board['bo_write_limit_text'] ?? 0);
                if ($_climit_text_pp > 0) {
                    $_excs_pp = trim((string)($board['bo_comment_limit_text_exc'] ?? ''));
                    $_excs_pp = $_excs_pp !== '' ? array_values(array_filter(array_map('trim', explode(',', $_excs_pp)), 'strlen')) : array();
                ?>
                <script>
                // mmbBindCommentLimit 정의 (list.skin.php 내 IIFE 와 동일 본체) — write 페이지에서도 사용 가능하도록 inline emit
                (function(){
                    if (window.mmbBindCommentLimit) return;
                    window.mmbBindCommentLimit = function(textareaEl, max, excList){
                        if (!textareaEl || !max || max <= 0) return;
                        var box = textareaEl.parentNode;
                        if (!box) return;
                        if (getComputedStyle(box).position === 'static') box.style.position = 'relative';
                        var badge = document.createElement('div');
                        badge.className = 'mmb-cl-badge';
                        box.appendChild(badge);
                        function count(){
                            var v = textareaEl.value || '';
                            if (excList && excList.length) {
                                for (var i=0;i<excList.length;i++){ v = v.split(excList[i]).join(''); }
                            }
                            var len = Array.from(v).length;
                            if (len > max) {
                                var raw = textareaEl.value, kept = '', n = 0;
                                var arr = Array.from(raw);
                                for (var j=0;j<arr.length;j++){
                                    var c = arr[j];
                                    var isExc = excList && excList.indexOf(c) !== -1;
                                    if (isExc) { kept += c; continue; }
                                    if (n >= max) {
                                        for (var k=j;k<arr.length;k++){ if (excList.indexOf(arr[k]) !== -1) kept += arr[k]; else break; }
                                        break;
                                    }
                                    kept += c; n++;
                                }
                                textareaEl.value = kept;
                                len = max;
                            }
                            badge.textContent = len + ' / ' + max + (excList && excList.length ? ' (제외:' + excList.join('') + ')' : '');
                        }
                        textareaEl.addEventListener('input', count);
                        textareaEl.addEventListener('keyup', count);
                        count();
                    };
                })();
                (function(){
                    var ta = document.getElementById('wr_content');
                    if (ta && window.mmbBindCommentLimit) window.mmbBindCommentLimit(ta, <?php echo $_climit_text_pp; ?>, <?php echo json_encode($_excs_pp, JSON_UNESCAPED_UNICODE); ?>);
                })();</script>
                <?php } ?>

                <?php if ($write_min || $write_max) { ?>
                <div id="char_count_wrap"><span id="char_count"></span>글자</div>
                <?php } ?>

                <p class="ui-btn help">
                    #해시태그 / @로그번호 / [[닉네임]]
                </p>
            </div>

            <hr class="padding">

            <div class="txt-center">
                <button type="submit" id="btn_submit" accesskey="s" class="ui-btn">COMMENT</button>
                <button type="button" onclick="location.href='./board.php?bo_table=<?php echo $bo_table; ?>';" class="ui-btn">LIST</button>
            </div>
            <?php if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('bbs_write_form_extra', '', $bo_table); ?>
            <?php if (function_exists('mdl_apply_filters')) echo mdl_apply_filters('bbs_write_form_after_buttons', '', $mmb_form_ctx); ?>
        </form>
        <?php if (function_exists('mdl_do_action')) mdl_do_action('bbs_write_form_after', $mmb_form_ctx); ?>

        <hr class="padding">
        <hr class="padding">
        <hr class="padding">
        <!-- } 게시물 작성/수정 끝 -->
    </section>
</div>


<script>
	<?php if($write_min || $write_max) { ?>
	// 글자수 제한
	var char_min = parseInt(<?php echo $write_min; ?>); // 최소
	var char_max = parseInt(<?php echo $write_max; ?>); // 최대
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
		}
		else
			obj.value = "";
	}

	function fwrite_submit(f)
	{
		<?php echo $editor_js; // 에디터 사용시 자바스크립트에서 내용을 폼필드로 넣어주며 내용이 입력되었는지 검사함   ?>

		var subject = "";
		var content = "";
		$.ajax({
			url: g5_bbs_url+"/ajax.filter.php",
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
			alert("제목에 금지단어('"+subject+"')가 포함되어있습니다");
			f.wr_subject.focus();
			return false;
		}

		if (content) {
			alert("내용에 금지단어('"+content+"')가 포함되어있습니다");
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
					alert("내용은 "+char_min+"글자 이상 쓰셔야 합니다.");
					return false;
				}
				else if (char_max > 0 && char_max < cnt) {
					alert("내용은 "+char_max+"글자 이하로 쓰셔야 합니다.");
					return false;
				}
			}
		}

<?php  if($w == '') { ?>
		if(f.wr_type.value == 'UPLOAD') {
			if(document.getElementById('wr_file').value == '') { 
				alert("업로드할 로그를 등록해 주시길 바랍니다.");
				return false;
			}
		} else if(f.wr_type.value == 'URL') { 
			if(document.getElementById('wr_url').value == '') { 
				alert("등록할 로그 URL을 입력해 주시길 바랍니다.");
				return false;
			}
		}
<?php  } ?>
		document.getElementById("btn_submit").disabled = "disabled";
		return true;
	}


	$('.view_image_area').on('change', function() {
		var image = $(this).val();
		var type = $(this).attr('type');

		if(type == 'file') {
			previewImage(this,'view_image');
		} else {
			$('#wr_file').replaceWith( $('#wr_file').clone(true) );

			checkImage(image, complete, '', 'view_image');
		}
	});

	function reset_image(previewId) {
		var preview = document.getElementById(previewId);
		var prevImg = document.getElementById("prev_" + previewId); //이전에 미리보기가 있다면 삭제
		if (prevImg) {
			preview.removeChild(prevImg);
		}

		$('#wr_width').val('');
		$('#wr_height').val('');

		$('#view_image > span').text("");
	}

	function previewImage(targetObj, previewId) {
		var preview = document.getElementById(previewId); //div id   
		var ua = window.navigator.userAgent;
		var files = targetObj.files;

		$('#view_image_loading').show();

		reset_image(previewId);

		for ( var i = 0; i < files.length; i++) {

			var file = files[i];

			var imageType = /image.*/; //이미지 파일일경우만.. 뿌려준다.
			if (!file.type.match(imageType)) {
				continue;
			}

			var img = document.createElement("img");
			img.id = "prev_" + previewId;
			img.classList.add("obj");
			img.file = file;

			if (window.FileReader) { // FireFox, Chrome, Opera 확인.
				var reader = new FileReader();
				reader.onloadend = (function(aImg) {
					return function(e) {
						aImg.src = e.target.result;
						complete('S', aImg.width, aImg.height);
						$('#view_image_loading').hide();
						preview.appendChild(img);
					};
				})(img);
				reader.readAsDataURL(file);
			} else { // safari is not supported FileReader
				//alert('not supported FileReader');
				if (!document.getElementById("sfr_preview_error_"
						+ previewId)) {
					var info = document.createElement("p");
					info.id = "sfr_preview_error_" + previewId;
					info.innerHTML = "not supported FileReader";
					preview.insertBefore(info, null);
				}
			}
		}
		
		if(i > 0) { 
			
			//preview.style.background="none";
		} else {
			complete('F');
		}
	}


	function checkImage(url, callback, timeout, previewId) {
		timeout = timeout || 5000;
		
		$('#view_image_loading').show();

		var timedOut = false, timer;
		var img = new Image();
		var preview = document.getElementById(previewId);

		reset_image(previewId);

		img.onerror = img.onabort = function() {
			if (!timedOut) {
				clearTimeout(timer);
				callback("F");
			}
		};
		img.onload = function() {
			if (!timedOut) {
				clearTimeout(timer);
				img.id = "prev_" + previewId;
				img.classList.add("obj");
				callback("S", img.width, img.height);
				preview.appendChild(img);
				$('#view_image_loading').hide();
			}
		};
		img.src = url;

		timer = setTimeout(function() {
			timedOut = true;
			callback("F");
		}, timeout); 
	}

	function complete(message, w, h) {
		if(message == 'S') { 
			$('#wr_width').val(w);
			$('#wr_height').val(h);
			$('#view_image > span').text("width : " + w + "px / height : " + h + "px");
		} else { 
			$('#view_image > span').text("");
		}
	}

	function fn_log_type(type) { 
		$('#add_'+type).siblings().hide();
		$('#add_'+type).show();

		$('#wr_url').val('');
		$('#wr_file').replaceWith( $('#wr_file').clone(true) );

		reset_image('view_image');
	}
</script>

<script>
// action 변경 → .comment-data 패널 토글은 코어(js/action_panel.js)가 전역 처리.

$('.change-thumb').on('change', function() {
	var select_item = $(this).find('option:selected');

	var thumb = select_item.data('thumb');

	if(typeof(thumb) != "undefined") {
		// 썸네일이 있는 경우
		$(this).closest('.has-thumb').find('.ui-thumb').empty().append("<img src='"+thumb+"' alt='' />");
	} else { 
		$(this).closest('.has-thumb').find('.ui-thumb').empty();
	}
});


$('#fwrite select.make-imtem').change(function() {
	var $sels = $('#fwrite select.make-imtem');
	$sels.find("option").attr('disabled', false);
	$sels.each(function() {
		if($(this).val()) {
			$sels.not(this).find("option[value="+ $(this).val() + "]").attr('disabled', true);
		}
	});
});


</script>
