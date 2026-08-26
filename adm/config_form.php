<?php
$sub_menu = "100900";
include_once('./_common.php');

if (!$is_admin) alert('관리자만 접근 가능합니다.');

$g5['title'] = '환경설정 (구버전 — 사용 중지)';
include_once('./admin.head.php');
?>

<div class="local_desc01 local_desc" style="background:#fff8e1; border-left:4px solid #f39c12; padding:14px 18px; margin-bottom:14px;">
	<h2 style="margin:0 0 8px; font-size:15px;">📦 이 페이지의 항목은 다른 페이지로 이전되었습니다</h2>
	<p style="margin:0; color:#666;">기존 <strong>환경설정 (config_form)</strong> 의 모든 항목이 분야별로 정리되어 각각의 관리 페이지로 이동했습니다.</p>
</div>

<div class="tbl_frm01 tbl_wrap">
<table>
<caption>이전된 항목 안내</caption>
<colgroup><col style="width:200px;"><col><col style="width:180px;"></colgroup>
<thead>
<tr><th>이전된 섹션</th><th>새 위치</th><th>바로가기</th></tr>
</thead>
<tbody>
<tr>
	<td>최고관리자 / 관리자 아이콘<br>에디터 선택<br>접속 제한 (IP)<br>레이아웃 추가설정 (script/css)</td>
	<td>커뮤니티 설정</td>
	<td><a href="./community_form.php" class="btn_frmline">커뮤니티 설정 →</a></td>
</tr>
<tr>
	<td>게시판 기본 설정<br>(글쓰기 간격, 페이지 표시, 새창 링크,<br>이미지·동영상 확장자, 단어 필터링,<br>한 페이지 줄 수, 검색 단위)</td>
	<td>게시판그룹 설정 (상단 패널)</td>
	<td><a href="./boardgroup_list.php" class="btn_frmline">게시판그룹 설정 →</a></td>
</tr>
<tr>
	<td>화폐 기능 사용 / 로그인 시 획득</td>
	<td>화폐 관리 (상단 패널)</td>
	<td><a href="./point_list.php" class="btn_frmline">화폐 관리 →</a></td>
</tr>
<tr>
	<td>회원가입 권한 / 가입 포인트 / 탈퇴 삭제일<br>닉네임 정책 / 금지단어 / 약관 / 유의사항<br>생년 입력 방식 / 추가 입력폼 / 항목별 도움말</td>
	<td>회원가입 설정</td>
	<td><a href="./member_register_form.php" class="btn_frmline">회원가입 설정 →</a></td>
</tr>
<tr>
	<td style="color:#aaa;">쪽지 삭제 기간</td>
	<td colspan="2" style="color:#aaa;">제거됨 (DB 기본값 유지, 자동 정리는 그대로 동작)</td>
</tr>
</tbody>
</table>
</div>

<p style="text-align:center; margin-top:16px; font-size:12px; color:#999;">
	이 페이지는 호환성을 위해 유지되며, 차후 버전에서 제거될 수 있습니다.
</p>

<?php
include_once('./admin.tail.php');
