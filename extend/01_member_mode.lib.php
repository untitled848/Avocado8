<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 멤버 모드 (Member Mode)
 *
 * 관리자가 마이페이지 → 관리자메뉴 → 멤버모드에서 켜면,
 * 일반 회원 시점으로 사이트가 보이도록 권한을 임시로 다운그레이드한다.
 *
 * 저장:
 *   {prefix}member.mb_mode  (TINYINT, 0=관리자, 1=멤버 모드)
 *
 * 동작:
 *   - $is_admin_real = $is_admin (백업)
 *   - $member['mb_real_level'] = 원본 레벨
 *   - $is_admin = ''  (= 관리자 아님)
 *   - $member['mb_level'] = 2  (일반 회원)
 *
 * 단, 마이페이지의 [관리자메뉴] 페이지는 $is_admin_real 로 접근 체크하므로
 * 멤버 모드 작동 중에도 진입 및 토글 가능.
 *
 * 주의: 슈퍼관리자(cf_admin) 는 mb_id 매칭으로 판정되므로, $config['cf_admin'] 자체를
 * 다시 부르지 않도록 mb_id 도 그대로 두고 mb_level/$is_admin 만 조정한다.
 * (auth_check 등 어드민 라이브러리는 $is_admin 만 본다)
 */

global $member, $is_admin, $g5;

// 스키마(member.mb_mode)는 install/gnuboard5.sql 에서 일괄 생성됩니다.

// 백업 (멤버 모드 여부와 무관하게 항상 백업: 다른 코드가 참조 가능하도록)
$GLOBALS['is_admin_real'] = $is_admin;
if (is_array($member) && !empty($member['mb_id'])) {
    $member['mb_real_level'] = isset($member['mb_level']) ? (int)$member['mb_level'] : 1;
}

// 멤버 모드 적용
if (!empty($is_admin) && is_array($member) && !empty($member['mb_mode'])) {
    $is_admin = '';
    $member['mb_level'] = 2; // 일반 회원
}

/**
 * 멤버 모드 활성 여부
 */
function is_member_mode_active()
{
    global $member;
    return !empty($member['mb_mode']) && !empty($member['mb_real_level']) && (int)$member['mb_real_level'] >= 10;
}

/**
 * 멤버 모드 안내 배너 (하위 호환 — print_admin_status_banner 로 통합됨)
 */
function print_member_mode_banner()
{
    print_admin_status_banner();
}

/**
 * 관리자 상태 통합 배너
 *
 * 테스트 시기 / 멤버 모드 중 하나 이상 활성 시 단일 배너로 출력
 * - iframe 내부에서는 JS로 자가 제거 (data-toplevel-only)
 * - 설정 링크: 마이페이지 관리자 메뉴 (index.php 통합)
 */
function print_admin_status_banner()
{
    global $config;
    $show_time = function_exists('is_time_override_active') && is_time_override_active();
    $show_mode = function_exists('is_member_mode_active') && is_member_mode_active();
    if (!$show_time && !$show_mode) return;

    if ($show_time && $show_mode) {
        $cls = 'asb-both';
    } elseif ($show_time) {
        $cls = 'asb-time';
    } else {
        $cls = 'asb-mode';
    }

    $admin_url = G5_URL.'/mypage/admin/';

    $parts = array();
    if ($show_time) {
        $real = (int)$config['cf_time_real'];
        $ov   = (int)$config['cf_time'];
        $real_info = get_time_by_order($real);
        $ov_info   = get_time_by_order($ov);
        $real_name = !empty($real_info['ti_name']) ? $real_info['ti_name'] : ('시기 '.$real);
        $ov_name   = !empty($ov_info['ti_name'])   ? $ov_info['ti_name']   : ('시기 '.$ov);
        $parts[] = '<span class="asb-time-text">테스트 시기 보기 중 — 지금: <strong class="asb-ov">'.h($ov_name).'</strong> (#'.$ov.') / 실제: <strong class="asb-real">'.h($real_name).'</strong> (#'.$real.')</span>';
    }
    if ($show_mode) {
        $parts[] = '<span class="asb-mode-text">멤버 모드 사용중 — 관리자 권한 일시 비활성화</span>';
    }
    $sep = count($parts) > 1 ? '&nbsp;&nbsp;<span class="asb-sep">|</span>&nbsp;&nbsp;' : '';
?>
<div id="admin-status-banner" class="<?php echo $cls; ?>">
    <?php echo implode($sep, $parts); ?>
    &nbsp;&nbsp;<a href="<?php echo $admin_url; ?>" class="asb-link">관리자 메뉴에서 변경</a>
</div>
<?php
}
