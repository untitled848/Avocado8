<?php
if (!defined('_GNUBOARD_')) exit;
/**
 * calculate 스킨 — 게시판 설정 훅
 * board_form.php 의 스킨 설정 섹션에 노출.
 *
 * bo_1 : 정산 신청 기능 사용 (1 = ON)
 *   ON 이면 글쓰기 폼에 "정산 신청" 블록 (프리셋 드롭다운 + 화폐/경험치 입력) 노출.
 *   글 작성자가 신청한 내용은 wr_link1 에 JSON 으로 저장되며,
 *   관리자가 글 보기에서 [확인] 버튼을 누르면 실제 지급이 실행된다.
 *   wr_10 = 정산완료/반려 (댓글 state). 글·댓글 수정 시 소실 방지: HOOK_CHECKLIST §L.
 */
?>
<div class="tbl_frm01 tbl_wrap">
<table>
<colgroup><col style="width:200px"><col></colgroup>
<tbody>
<tr>
    <th scope="row"><label for="bo_1">정산 신청 허용</label></th>
    <td>
        <label><input type="checkbox" name="bo_1" id="bo_1" value="1" <?php echo (!empty($board['bo_1']) && $board['bo_1'] === '1') ? 'checked' : ''; ?>> 사용</label>
        <input type="hidden" name="bo_1_subj" value="정산신청">
        <p class="frm_info">활성 시 작성자는 글쓰기 폼에서 정산 프리셋(정산 신청 ON 으로 설정된 것)·화폐·경험치를 신청할 수 있고, 관리자가 글 보기에서 [확인] 으로 지급합니다.</p>
    </td>
</tr>
</tbody>
</table>
</div>
