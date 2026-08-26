<?php 
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가 
?>

<script language="JavaScript">
// 글자수 제한
var char_min = parseInt(<?php echo $comment_min?>); // 최소
var char_max = parseInt(<?php echo $comment_max?>); // 최대
</script>

<!-- 코멘트 리스트 -->

<ul>
<!-- 코멘트 리스트 -->
<?php 
for ($i=0; $i<count($list); $i++) {
	$comment_id = $list[$i]['wr_id'];
?>
			
	<li>
		<a name="c_<?php echo $comment_id?>"></a>
		<div class="qna-comment-content theme-box">
			<!-- 코멘트 출력 -->
			<?php 
			if (strstr($list[$i]['wr_option'], "secret")) echo "<span style='color:#ff6600;'>*</span> ";
			$str = $list[$i]['content'];
			if (strstr($list[$i]['wr_option'], "secret"))
				$str = "<span class='small' style='color:#ff6600;'>$str</span>";

			$str = preg_replace("/\[\<a\s.*href\=\"(http|https|ftp|mms)\:\/\/([^[:space:]]+)\.(mp3|wma|wmv|asf|asx|mpg|mpeg)\".*\<\/a\>\]/i", "<script>doc_write(obj_movie('$1://$2.$3'));</script>", $str);
			$str = preg_replace("/\[\<a\s.*href\=\"(http|https|ftp)\:\/\/([^[:space:]]+)\.(swf)\".*\<\/a\>\]/i", "<script>doc_write(flash_movie('$1://$2.$3'));</script>", $str);
			$str = preg_replace("/\[\<a\s*href\=\"(http|https|ftp)\:\/\/([^[:space:]]+)\.(gif|png|jpg|jpeg|bmp)\"\s*[^\>]*\>[^\s]*\<\/a\>\]/i", "<img src='$1://$2.$3' id='target_resize_image[]' onclick='image_window(this);' border='0'>", $str);
			echo $str;
			?>
		</div>
		<p>
			<em>&nbsp;</em>
			<strong>
				<?php  if ($list[$i]['is_del'])  { echo "<span class=\"v7\"><a href=\"javascript:comment_delete('{$list[$i]['del_link']}');\">D</a></span>&nbsp;"; } ?>
			</strong>
		</p>
	</li>
<?php  } ?>
</ul>

<?php  if ($is_comment_write) { ?>
<div class="ui-write-area" id="comment_write<?php echo $wr_id?>" style="display:none;">
	<!-- 코멘트 입력테이블시작 -->
	<form name="fviewcomment<?php echo $wr_id?>" method="post" action="./write_comment_update.php" autocomplete="off">
		<input type="hidden" name="w"			value='c'>
		<input type="hidden" name="bo_table"	value='<?php echo $bo_table?>'>
		<input type="hidden" name="wr_id"		value='<?php echo $wr_id?>'>
		<input type="hidden" name="comment_id"	value=''>
		<input type="hidden" name="token"		value='<?php echo $comment_token?>'>
		<input type="hidden" name="sca"			value='<?php echo $sca?>' >
		<input type="hidden" name="sfl"			value='<?php echo $sfl?>' >
		<input type="hidden" name="stx"			value='<?php echo $stx?>'>
		<input type="hidden" name="spt"			value='<?php echo $spt?>'>
		<input type="hidden" name="page"		value='<?php echo $page?>'>
		<input type="hidden" name="cwin"		value='<?php echo $cwin?>'>
		<input type="hidden" name="is_good"		value=''>
		<input type="hidden" name="url"			value='./board.php?bo_table=<?php echo $bo_table?>&page=<?php echo $page?>'>
		

		<textarea id="wr_content" name="wr_content" rows=4 itemname="내용" required
		<?php  if ($comment_min || $comment_max) { ?>onkeyup="check_byte('wr_content', 'char_count');"<?php }?> style='width:100%; word-break:break-all;' class='tx'><?php echo $list[$i]['wr_content']?></textarea>
		<?php  if ($comment_min || $comment_max) { ?><script type="text/javascript"> check_byte('wr_content', 'char_count'); </script><?php }?>

		<div class="txt-right">
			<button type="submit" id="btn_submit" class="ui-btn" accesskey='s'>ENTER</button>
		</div>
	</form>
</div>
<?php  } ?>

<script language='JavaScript'>
function fviewcomment<?php echo $wr_id?>_submit(f)
{
    return true;
}
</script>
<?php  
include_once("$board_skin_path/view_skin_js.php");
?>