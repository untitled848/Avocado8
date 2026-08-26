<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

$update_href = $delete_href = '';

// 로그인중이고 자신의 글이라면 또는 관리자라면 비밀번호를 묻지 않고 바로 수정, 삭제 가능
if (($member['mb_id'] && ($member['mb_id'] == $list_item['mb_id'])) || $is_admin) {

	$update_href = './write.php?w=u&amp;bo_table='.$bo_table.'&amp;wr_id='.$list_item['wr_id'].'&amp;page='.$page.$qstr;
	if(!$list_item['wr_log'] || $is_admin) { 
		set_session('ss_delete_token', $token = uniqid(time()));
		$delete_href ='./delete.php?bo_table='.$bo_table.'&amp;wr_id='.$list_item['wr_id'].'&amp;token='.$token.'&amp;page='.$page.urldecode($qstr);
	}
}
else if (!$list_item['mb_id']) { // 회원이 쓴 글이 아니라면
	$update_href = './password.php?w=u&amp;bo_table='.$bo_table.'&amp;wr_id='.$list_item['wr_id'].'&amp;page='.$page.$qstr;
	$delete_href = './password.php?w=d&amp;bo_table='.$bo_table.'&amp;wr_id='.$list_item['wr_id'].'&amp;page='.$page.$qstr;
}

// 즐겨찾기 (스크랩) 여부 체크
$is_favorite = sql_fetch("select count(*) as cnt from {$g5['scrap_table']} where mb_id = '{$member['mb_id']}' and wr_id = '{$list_item['wr_id']}' and bo_table = '{$bo_table}'");
$is_favorite = $is_favorite['cnt'] > 0 ? true : false;

// ── 로그 이미지/클래스 계산 ──
//   래퍼 div(.item / .ui-pic / .pic-data)의 class·속성에 반영되어야 하므로
//   HTML 출력 전(여기, 컨트롤러 영역)에서 미리 끝낸다.
//   (출력은 template/list_log_img.tpl.php 가 담당)
$image_url = '';
$image_width = 0;
$image_height = 0;

if($list_item['wr_type'] == 'UPLOAD') {
	// Upload 형태로 로그를 등록 하였을 때
	$thumb = get_mmb_image($bo_table, $list_item['wr_id']);
	$image_url = '<img src="'.$thumb['src'].'" />';
	$image_width = $thumb['width'];
	$image_height = $thumb['height'];
} else if($list_item['wr_type'] == 'URL') {
	// URL 형태로 로그를 등록 하였을 때
	$image_url = '<img src="'.$list_item['wr_url'].'" />';
	$image_width = $list_item['wr_width'];
	$image_height = $list_item['wr_height'];
}
// 그 외(모듈이 추가한 wr_type)는 list_log_img.tpl.php 의 모듈 훅이 출력을 담당한다.
//   단, 갤러리 레이아웃(data_width / bo_gallery_height 접기)을 위해 저장된
//   wr_width/wr_height 를 대표 이미지 크기로 사용한다.
if($image_url === '' && $list_item['wr_width']) {
	$image_width = $list_item['wr_width'];
	$image_height = $list_item['wr_height'];
}

$log_class = '';
$blind_class = '';
$h_class = '';

// 멤버공개 데이터일 시
$is_viewer = true;
$data_width = 300;
$no_member_class = '';
if($list_item['wr_secret'] == '1' && !$is_member) {
	$is_viewer = false;
	$no_member_class = ' empty ';
} else {
	$data_width = $image_width < 300 ? 300 : $image_width;
}

if($is_viewer) {

	// 접기 여부 설정
	if($board['bo_gallery_height'] && ($image_height >= $board['bo_gallery_height'] || $list_item['wr_plip'] == '1')) {
		if(G5_IS_MOBILE) {
			$log_class .= "ui-slide-mobile";
			if($list_item['wr_type'] == 'UPLOAD') {
				$thumb = get_list_thumbnail($bo_table, $list_item['wr_id'], $image_width, 200, true, true);
				$ori = explode("/", $thumb['ori']);
				$ori = $ori[count($ori) -1];
				$image_url = '<a href="'.G5_BBS_URL.'/view_image.php?bo_table='.$board['bo_table'].'&amp;fn='.urlencode($ori).'" target="_blank" class="view_image">';
				$image_url .= '<img src="'.$thumb['src'].'" alt="'.$content.'" '.$attr.'>';
				$image_url .= '</a>';
				$image_width = $thumb['width'];
				$image_height = $thumb['height'];
			} else if($list_item['wr_type'] == 'URL') {
				$image_url = '<a href="'.$list_item['wr_url'].'" target="_blank" class="view_image">';
				$image_url .= '<img src="'.$list_item['wr_url'].'" alt="'.$content.'" '.$attr.'>';
				$image_url .= '</a>';
			}
		} else {
			$log_class .= "ui-slide";
		}
	}
	// 블라인드 (19금 필터링) 여부 설정
	if($list_item['wr_adult'] == '1') {
		$blind_class = "ui-blind";
	}
	// 리플 아래로 내리기 여부 설정
	if($list_item['wr_wide'] == '1') {
		$h_class = "ui-wrap";
	}
}

if (!empty($list_item['wr_k_raid_admin'])) {
	$h_class = 'raid_admin';
}

$mmb_row_extra_ctx = array(
	'bo_table'        => isset($bo_table) ? $bo_table : '',
	'board_skin_url'  => isset($board_skin_url) ? $board_skin_url : '',
	'board_skin_path' => isset($board_skin_path) ? $board_skin_path : '',
	'scope'           => 'post',
);
$mmb_row_has_raid = !empty($list_item['wr_k_raid_log']) || !empty($list_item['wr_k_raid_admin']);

// 알람 내역이 있을 경우, 확인으로 체크
sql_query("update {$g5['call_table']} set bc_check = 1 where re_mb_id = '{$member['mb_id']}' and bo_table ='{$bo_table}' and wr_id = '{$list_item['wr_id']}'");

// 로그 이미지 출력 템플릿(list_log_img.tpl.php) 에 전달할 모듈 훅 컨텍스트
$mmb_log_ctx = array(
	'bo_table'     => $bo_table,
	'list_item'    => $list_item,
	'wr_type'      => $list_item['wr_type'],
	'image_url'    => $image_url,
	'image_width'  => $image_width,
	'image_height' => $image_height,
	'log_class'    => $log_class,
	'blind_class'  => $blind_class,
	'is_viewer'    => $is_viewer,
	'board'        => $board,
);
?>

<div class="item <?php echo $h_class?>" id="log_<?php echo $list_item['wr_id']?>">
	<div class="item-inner">
	<!--  로그 이미지 출력 부분 -->
		<div class="ui-pic <?php echo $no_member_class?>" data-width="<?php echo $data_width?>">

			<!-- 로그 상단 영역 -->
			<div class="pic-header">
				<p class="no">
					
					<?php  // 로그 넘버링 출력 ?>
					No. <?php echo ($list_item['wr_num'] * -1)?>

					<?php  if($list_item['ca_name']){ 
						// 카테고리 출력
					?>
					<span data-category="<?php echo $list_item['ca_name']?>" class="ico-category">
						<?php echo $list_item['ca_name']?>
					</span>
					<?php  } ?>
					<?php  if($list_item['wr_adult']) {
						// 19금 필터링 마크
					?>
					<span style="color:#d3393d;">■</span>
					<?php  } ?>
				</p>

				<?php  if($is_viewer) { 
					// 보기 권한이 존재 할 경우 (멤버의 경우)
					// -- 버튼 영역 출력
					if ($delete_href)		{ ?><a href="<?php echo $delete_href ?>" class="del" onclick="del(this.href); return false;">삭제</a><?php 	} ?>
												<a href="?bo_table=<?php echo $bo_table?>&log=<?php echo $list_item['wr_num'] * -1?>&single=Y" target="_blank" class="new">로그링크</a>
					<?php  if($is_member)		{ ?><a href="#" data-idx='<?php echo $list_item['wr_id']?>' data-function="favorite" class="fav <?php echo $is_favorite ? "on" : ""?>">관심</a><?php  } ?>
					<?php  if ($update_href)	{ ?><a href="<?php echo $update_href ?>" class="mod">수정</a><?php  } ?>
				<?php  } ?>
			</div>
			<!-- // 로그 상단 영역 -->

			<!-- 로그 이미지 -->
			<div class="pic-data <?php echo $log_class?> <?php echo !$member['mb_adult'] ? $blind_class : ""?>">
			<?php  if(!$is_viewer) { 
				// 비공개 이미지
			?>
				<div>
					<img src="<?php echo $board_skin_url?>/img/img_lock.png" alt="비공개" />
				</div>
			<?php  } else { 
				include(G5_BBS_PATH.'/mmb/template/list_log_img.tpl.php');
			 } ?>
			</div>	
			<!-- // 로그 상단 영역 -->

		</div>
	<!--  // 로그 이미지 출력 부분 -->

	<!--  로그 코멘트 출력 부분 -->
		<div class="ui-comment">
		<?php  if($is_viewer) {  ?>
			<div class="item-comment-box">
				<?php
				$mmb_write_as_comment = (isset($list_item['wr_content']) && trim((string)$list_item['wr_content']) !== '');
				// 원글 본문이 댓글 칸에 나가면 액션 로그도 그쪽에서 그린다 (댓글과 동일 구조).
				if (!$mmb_write_as_comment && function_exists('bbs_render_row_extra')) {
					bbs_render_row_extra($list_item, $mmb_row_extra_ctx);
				}
				include($board_skin_path."/view_comment.php");
				?>
			</div>
			<div class="item-comment-form-box">
				<?php  include($board_skin_path."/write_comment.php");?>
			</div>
		<?php  } else { ?>
			멤버 공개용 로그 입니다.
		<?php  } ?>
		</div>
	<!-- // 로그 코멘트 출력 부분 -->
	</div>
</div>
