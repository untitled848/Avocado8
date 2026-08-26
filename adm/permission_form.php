<?php
$sub_menu = "100600";
include_once('./_common.php');

$g5['title'] = '통합 권한관리';
include_once('./admin.head.php');

// ────────────────────────────────────────────────
// 레벨 매핑 헬퍼
// 1 = 방문자(비회원 포함), 2 = 일반멤버, 10 = 관리자
// 그 외 기존 값(3~9)은 "기타(N)" 옵션으로 보존
// ────────────────────────────────────────────────
function perm_level_select($name, $cur)
{
    $cur = intval($cur);
    $opts = [1 => '방문자', 2 => '일반멤버', 10 => '관리자'];
    $html = '<select name="'.htmlspecialchars($name, ENT_QUOTES).'">';
    foreach ($opts as $v => $label) {
        $html .= '<option value="'.$v.'"'.($cur === $v ? ' selected' : '').'>'.$label.' ('.$v.')</option>';
    }
    if (!isset($opts[$cur]) && $cur > 0) {
        $html .= '<option value="'.$cur.'" selected>기타 ('.$cur.')</option>';
    }
    $html .= '</select>';
    return $html;
}

// 게시판 목록
$boards = [];
$res = sql_query("SELECT bo_table, bo_subject, bo_list_level, bo_read_level, bo_write_level, bo_comment_level, bo_download_level FROM {$g5['board_table']} ORDER BY bo_order, bo_table", false);
if ($res) {
    while ($b = sql_fetch_array($res)) $boards[] = $b;
}

$contents = [];
if (!empty($g5['content_table'])) {
    $cres = sql_query("SELECT co_id, co_subject FROM {$g5['content_table']} WHERE co_subject != '' ORDER BY co_id", false);
    if ($cres) {
        while ($c = sql_fetch_array($cres)) $contents[] = $c;
    }
}

$fallback_raw   = trim((string)($config['cf_perm_fallback_url'] ?? ''));
$fallback_url   = htmlspecialchars($fallback_raw, ENT_QUOTES);
$fallback_co_id = '';
if ($fallback_raw !== '' && strpos($fallback_raw, 'content.php') !== false) {
    $qstr = parse_url($fallback_raw, PHP_URL_QUERY);
    if ($qstr === null && strpos($fallback_raw, '?') !== false) {
        $qstr = substr($fallback_raw, strpos($fallback_raw, '?') + 1);
    }
    $qs = [];
    parse_str((string)$qstr, $qs);
    if (isset($qs['co_id']) && $qs['co_id'] !== '') {
        $fallback_co_id = (string)$qs['co_id'];
    }
}
$scope          = (string)($config['cf_open_scope'] ?? 'all');
if (!in_array($scope, ['all','member','admin'], true)) $scope = 'all';
$cf_1           = intval($config['cf_1'] ?? 1); // 계정생성 가능
$cf_2           = intval($config['cf_2'] ?? 1); // 캐릭터생성 가능
$cf_character_count = intval($config['cf_character_count'] ?? 1);
$cf_character_edit_admin_only = intval($config['cf_character_edit_admin_only'] ?? 0);
$char_edit_level = $cf_character_edit_admin_only ? 10 : 2;
// 페이지별 권한은 PERM_LV_GUEST(1) / MEMBER(2) / ADMIN(10) 수준
$page_level_opts = [1 => '방문자', 2 => '일반회원', 10 => '운영자'];
function perm_page_level_select($name, $cur)
{
    global $page_level_opts;
    $cur = (int)$cur; if ($cur <= 0) $cur = 1;
    $html = '<select name="'.htmlspecialchars($name, ENT_QUOTES).'" id="'.htmlspecialchars($name, ENT_QUOTES).'">';
    foreach ($page_level_opts as $v => $label) {
        $html .= '<option value="'.$v.'"'.($cur === $v ? ' selected' : '').'>'.$label.'</option>';
    }
    if (!isset($page_level_opts[$cur])) {
        $html .= '<option value="'.$cur.'" selected>기타 ('.$cur.')</option>';
    }
    $html .= '</select>';
    return $html;
}

$cf_perm_ready  = intval($config['cf_perm_ready']  ?? 1);
$cf_perm_member = intval($config['cf_perm_member'] ?? 1);
$cf_perm_memo   = intval($config['cf_perm_memo']   ?? 2);
$cf_perm_shop   = intval($config['cf_perm_shop']   ?? 1);

// 운영 단계별 권한 프리셋. 명시한 항목만 폼에 채운다.
$perm_bulk_presets = [
    'soft_open' => [
        'title'   => '가개장',
        'summary' => '계정 생성 허용, 캐릭터 생성 불가, 캐릭터 수정 관리자만, 신청자 목록 운영자, 쪽지 운영자, 상점 운영자',
        'fields'  => [
            'cf_1'                         => 1,
            'cf_2'                         => 0,
            'cf_character_edit_admin_only' => 1,
            'cf_perm_ready'                => 10,
            'cf_perm_memo'                 => 10,
            'cf_perm_shop'                 => 10,
        ],
    ],
    'pre_register' => [
        'title'   => '신청서 사전등록',
        'summary' => '계정 생성 허용, 캐릭터 생성 허용, 캐릭터 수정 회원 허용, 신청자 목록 운영자, 쪽지 운영자, 상점 운영자',
        'fields'  => [
            'cf_1'                         => 1,
            'cf_2'                         => 1,
            'cf_character_edit_admin_only' => 0,
            'cf_perm_ready'                => 10,
            'cf_perm_memo'                 => 10,
            'cf_perm_shop'                 => 10,
        ],
    ],
    'apply_period' => [
        'title'   => '신청기간',
        'summary' => '계정 생성 허용, 캐릭터 생성 허용, 캐릭터 수정 회원 허용, 신청자 목록 방문자, 쪽지 운영자, 상점 운영자',
        'fields'  => [
            'cf_1'                         => 1,
            'cf_2'                         => 1,
            'cf_character_edit_admin_only' => 0,
            'cf_perm_ready'                => 1,
            'cf_perm_memo'                 => 10,
            'cf_perm_shop'                 => 10,
        ],
    ],
    'launch_prep' => [
        'title'   => '합발 준비',
        'summary' => '공개 범위 일반회원, 계정 생성 불가, 캐릭터 생성 불가, 캐릭터 수정 운영자, 신청자 목록 운영자, 멤버란 운영자, 쪽지 운영자, 상점 운영자',
        'fields'  => [
            'cf_open_scope'                => 'member',
            'cf_1'                         => 0,
            'cf_2'                         => 0,
            'cf_character_edit_admin_only' => 1,
            'cf_perm_ready'                => 10,
            'cf_perm_member'               => 10,
            'cf_perm_memo'                 => 10,
            'cf_perm_shop'                 => 10,
        ],
    ],
    'running' => [
        'title'   => '러닝기간',
        'summary' => '공개 범위 일반회원, 계정 생성 불가, 캐릭터 생성 불가, 캐릭터 수정 운영자, 신청자 목록 회원, 멤버란 회원, 쪽지 회원, 상점 회원',
        'fields'  => [
            'cf_open_scope'                => 'member',
            'cf_1'                         => 0,
            'cf_2'                         => 0,
            'cf_character_edit_admin_only' => 1,
            'cf_perm_ready'                => 2,
            'cf_perm_member'               => 2,
            'cf_perm_memo'                 => 2,
            'cf_perm_shop'                 => 2,
        ],
    ],
];

if (isset($_GET['saved'])) echo '<div style="background:#e8ffe8;border:1px solid #cde6cd;padding:10px;margin-bottom:10px;">설정이 저장되었습니다.</div>';
?>

<form name="fform" id="fform" method="post" action="./permission_form_update.php" autocomplete="off">
<input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">

<section>
<h2 class="h2_frm">권한 일괄 수정</h2>
<p class="frm_info" style="margin:5px 0 10px;">단계를 고르면 아래 해당 항목이 채워집니다. <strong>모든 설정 저장</strong>을 눌러야 반영됩니다. 지정하지 않은 항목(접근 거부 URL, 게시판 권한, 캐릭터 최대 개수 등)은 그대로 둡니다.</p>
<div class="local_ov01 local_ov" style="display:flex;flex-wrap:wrap;align-items:flex-start;gap:8px 12px;margin-bottom:14px;">
  <label for="perm_bulk_preset" style="font-weight:bold;line-height:28px;">단계</label>
  <select id="perm_bulk_preset" style="min-width:180px;">
    <option value="">단계 선택</option>
    <?php foreach ($perm_bulk_presets as $pkey => $pp): ?>
    <option value="<?php echo htmlspecialchars($pkey, ENT_QUOTES); ?>"><?php echo htmlspecialchars($pp['title'], ENT_QUOTES); ?></option>
    <?php endforeach; ?>
  </select>
  <p id="perm_bulk_summary" class="frm_info" style="margin:0;flex:1;min-width:280px;line-height:1.6;"></p>
</div>
</section>

<section style="margin-top:20px;">
<h2 class="h2_frm">사이트 전체</h2>
<div class="tbl_frm01 tbl_wrap">
<table>
<colgroup><col style="width:200px"><col></colgroup>
<tbody>
<tr>
  <th scope="row">공개 범위</th>
  <td>

    <select name="cf_open_scope" id="cf_open_scope">
      <option value="all"    <?php echo $scope==='all'    ? 'selected' : ''; ?>>전체공개 (비회원 포함)</option>
      <option value="member" <?php echo $scope==='member' ? 'selected' : ''; ?>>회원만 접근 가능 (멤버공개)</option>
      <option value="admin"  <?php echo $scope==='admin'  ? 'selected' : ''; ?>>관리자만 접근 가능 (전체 잠금)</option>
    </select>
    <?php echo help('멤버공개여도 아래 [계정 생성 허용]이 켜져 있으면 비회원이 회원가입 페이지에 접근할 수 있습니다. 관리자 잠금에서는 회원가입이 허용되어 있어도 가입할 수 없습니다.'); ?>
  </td>
</tr>
<tr>
  <th scope="row">접근 거부 시 이동할 URL</th>
  <td>
    <input type="text" name="cf_perm_fallback_url" id="cf_perm_fallback_url" value="<?php echo $fallback_url; ?>" class="frm_input" style="width:360px" placeholder="비워두면 /warning.php">
    <?php if (!empty($contents)) { ?>
    <select id="cf_perm_fallback_content" style="margin-left:6px;">
      <option value="">문서에서 선택…</option>
      <?php foreach ($contents as $c) {
          $opt_url = G5_BBS_URL.'/content.php?co_id='.rawurlencode($c['co_id']);
          $sel = ($fallback_co_id !== '' && $fallback_co_id === (string)$c['co_id']) ? ' selected' : '';
      ?>
      <option value="<?php echo htmlspecialchars($opt_url, ENT_QUOTES); ?>"<?php echo $sel; ?>><?php echo htmlspecialchars($c['co_subject'].' ('.$c['co_id'].')', ENT_QUOTES); ?></option>
      <?php } ?>
    </select>
    <script>
    (function(){
      var sel = document.getElementById('cf_perm_fallback_content');
      var inp = document.getElementById('cf_perm_fallback_url');
      if (!sel || !inp) return;
      sel.addEventListener('change', function(){
        if (this.value) inp.value = this.value;
      });
    })();
    </script>
    <?php } ?>
    <?php echo help('내부 주소(내용관리 문서 등)를 넣을 수 있습니다. 사이트를 관리자만 잠그면 그 페이지만 로그인한 비관리자가 볼 수 있고, 비회원은 로그인 페이지로 갑니다. 비우거나 그 주소에서도 막히면 /warning.php 로 이동합니다.'); ?>
  </td>
</tr>
</tbody>
</table>
</div>
</section>

<section style="margin-top:20px;">
<h2 class="h2_frm">계정 / 캐릭터</h2>
<div class="tbl_frm01 tbl_wrap">
<table>
<colgroup><col style="width:200px"><col></colgroup>
<tbody>
<tr>
  <th scope="row">계정 생성</th>
  <td>
    <label><input type="checkbox" name="cf_1" id="cf_1" value="1" <?php echo $cf_1 ? 'checked' : ''; ?>> 계정 생성(회원가입) 허용</label>
    <?php echo help('멤버공개 상태에서도 이 항목이 켜져 있으면 로그인 화면에서 회원가입이 가능합니다.'); ?>
  </td>
</tr>
<tr>
  <th scope="row">캐릭터 생성</th>
  <td>
    <label><input type="checkbox" name="cf_2" id="cf_2" value="1" <?php echo $cf_2 ? 'checked' : ''; ?>> 캐릭터 생성 허용</label>
    &nbsp;&nbsp;아이디 당 최대
    <input type="number" name="cf_character_count" value="<?php echo $cf_character_count; ?>" min="0" style="width:60px"> 개
    <small style="color:#888">(0 = 무제한)</small>
  </td>
</tr>
<tr>
  <th scope="row">캐릭터 수정</th>
  <td>
    <select name="cf_character_edit_admin_only" id="cf_character_edit_admin_only">
      <option value="0" <?php echo $cf_character_edit_admin_only ? '' : 'selected'; ?>>회원 허용 (레벨 2)</option>
      <option value="1" <?php echo $cf_character_edit_admin_only ? 'selected' : ''; ?>>관리자만 허용 (레벨 10)</option>
    </select>
  </td>
</tr>
</tbody>
</table>
</div>
</section>

<section style="margin-top:20px;">
<h2 class="h2_frm">페이지별 접근 권한</h2>
<p class="frm_info" style="margin:5px 0 10px;">각 페이지별로 접근 가능한 최소 등급을 지정합니다. <strong>방문자 / 일반회원 / 운영자</strong> 3단계. 운영자는 설정과 무관하게 항상 접근 가능합니다.</p>
<div class="tbl_frm01 tbl_wrap">
<table>
<colgroup><col style="width:200px"><col></colgroup>
<tbody>
<tr><th scope="row">신청자 목록/프로필</th><td><?php echo perm_page_level_select('cf_perm_ready',  $cf_perm_ready); ?></td></tr>
<tr><th scope="row">멤버란</th><td><?php echo perm_page_level_select('cf_perm_member', $cf_perm_member); ?></td></tr>
<tr><th scope="row">쪽지 기능</th><td><?php echo perm_page_level_select('cf_perm_memo',   $cf_perm_memo); ?></td></tr>
<tr><th scope="row">상점 페이지</th><td><?php echo perm_page_level_select('cf_perm_shop',   $cf_perm_shop); ?></td></tr>
</tbody>
</table>
</div>
</section>

<section style="margin-top:20px;">
<h2 class="h2_frm">게시판 권한</h2>
<p class="frm_info" style="margin:5px 0 10px;">권한 등급은 <strong>방문자(1) / 일반멤버(2) / 관리자(10)</strong> 3단계로 단순화되었습니다. 게시판 이름을 클릭하면 해당 게시판 설정으로 이동합니다.</p>
<div class="tbl_head01 tbl_wrap">
<table>
<colgroup>
  <col><col style="width:120px"><col style="width:120px"><col style="width:120px"><col style="width:120px"><col style="width:120px">
</colgroup>
<thead>
<tr>
  <th scope="col">게시판</th>
  <th scope="col">목록</th>
  <th scope="col">읽기</th>
  <th scope="col">쓰기</th>
  <th scope="col">댓글</th>
  <th scope="col">다운로드</th>
</tr>
</thead>
<tbody>
<?php foreach ($boards as $b): ?>
<tr>
  <td style="text-align:left;">
    <a href="./board_form.php?w=u&amp;bo_table=<?php echo urlencode($b['bo_table']); ?>"><?php echo htmlspecialchars($b['bo_subject'], ENT_QUOTES); ?></a>
    <small style="color:#888;">(<?php echo htmlspecialchars($b['bo_table'], ENT_QUOTES); ?>)</small>
  </td>
  <td style="text-align:center;"><?php echo perm_level_select('bo_list_level['.$b['bo_table'].']', $b['bo_list_level']); ?></td>
  <td style="text-align:center;"><?php echo perm_level_select('bo_read_level['.$b['bo_table'].']', $b['bo_read_level']); ?></td>
  <td style="text-align:center;"><?php echo perm_level_select('bo_write_level['.$b['bo_table'].']', $b['bo_write_level']); ?></td>
  <td style="text-align:center;"><?php echo perm_level_select('bo_comment_level['.$b['bo_table'].']', $b['bo_comment_level']); ?></td>
  <td style="text-align:center;"><?php echo perm_level_select('bo_download_level['.$b['bo_table'].']', $b['bo_download_level']); ?></td>
</tr>
<?php endforeach; ?>
<?php if (empty($boards)): ?>
<tr><td colspan="6" class="empty_table">등록된 게시판이 없습니다.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</section>

<div class="btn_confirm01 btn_confirm">
  <input type="submit" value="모든 설정 저장" class="btn_submit">
</div>
</form>

<script>
(function(){
  var presets = <?php echo json_encode($perm_bulk_presets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
  var form = document.getElementById('fform');
  var sel = document.getElementById('perm_bulk_preset');
  var summary = document.getElementById('perm_bulk_summary');
  if (!form || !sel) return;

  var checkboxes = { cf_1: 1, cf_2: 1 };

  function setField(name, value) {
    var el = form.elements[name];
    if (!el) return;
    if (checkboxes[name]) {
      el.checked = !!value;
    } else {
      el.value = String(value);
    }
    var row = el.closest ? el.closest('tr') : null;
    if (row) {
      row.style.background = '#fff8e0';
      window.setTimeout(function(){ row.style.background = ''; }, 1200);
    }
  }

  sel.addEventListener('change', function(){
    var key = this.value;
    if (!key || !presets[key]) {
      if (summary) summary.textContent = '';
      return;
    }
    var p = presets[key];
    if (summary) summary.textContent = p.summary || '';
    var fields = p.fields || {};
    for (var name in fields) {
      if (Object.prototype.hasOwnProperty.call(fields, name)) {
        setField(name, fields[name]);
      }
    }
  });
})();
</script>

<?php include_once('./admin.tail.php'); ?>
