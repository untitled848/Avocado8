<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 아이템 사용 로그 템플릿 (게시판 글/댓글의 wr_item_log 표시)
 *
 * $item_log : explode('||', wr_item_log) 결과 배열
 *   [0] 타입 코드
 *     - D : 일반 아이템 사용
 *     - S : 뽑기 성공
 *     - F : 뽑기 실패
 *     - P : 아이템 선물 (present)
 *     - 그 외 : 다른 모듈이 추가한 타입 → default 절의 hook 으로 위임
 *
 *   D : [1]it_id [2]it_name [3]it_type [4]it_value [5]it_content [6]it_content2
 *   S : [1]it_id [2]it_name [3]got_it_id [4]got_it_name
 *   F : [1]it_id [2]it_name
 *   P : [1]it_id [2]it_name [3]re_ch_id [4]re_ch_name
 *
 * ▶ 다른 모듈이 새 타입을 추가할 때:
 *      mdl_add_filter('item_log_render_extra', function ($html, $item_log) {
 *          if (($item_log[0] ?? '') !== 'X') return $html;          // 자기 타입만 처리
 *          return '<div class="log-action-box my-type">...</div>';   // 비어있지 않으면 출력
 *      }, 10);
 */

$_type = isset($item_log[0]) ? (string)$item_log[0] : '';

switch ($_type) {

    case 'D': /* 일반 아이템 사용 */
        if (!empty($item_log[1])) { ?>
            <div class="log-action-box data-item">
                <em><img src="<?php echo get_item_img($item_log[1]); ?>" /></em>
                <p>
                    <span><strong><?php echo $item_log[2]; ?></strong><?php echo j($item_log[2], '을'); ?> 사용했습니다!</span>
                </p>
                <?php echo isset($item_log[5]) ? '<p class="it-content">'.$item_log[5].'</p>' : ''; ?>
                <?php echo isset($item_log[6]) ? '<p class="it-content2">'.$item_log[6].'</p>' : ''; ?>
            </div>
        <?php }
        break;

    case 'S': /* 뽑기 성공 */ ?>
        <div class="log-action-box data-item">
            <em><img src="<?php echo get_item_img($item_log[3]); ?>" /></em>
            <p>
                <span><strong><?php echo $item_log[2]; ?></strong><?php echo j($item_log[2], '을'); ?> 사용해서 <strong><?php echo $item_log[4]; ?></strong><?php echo j($item_log[4], '을'); ?> 획득하였습니다!</span>
            </p>
        </div>
        <?php
        break;

    case 'F': /* 뽑기 실패 */ ?>
        <div class="log-action-box data-item">
            <p>
                <span><strong><?php echo $item_log[2]; ?></strong><?php echo j($item_log[2], '을'); ?> 사용했지만 <strong><?php echo isset($item_log[5]) ? $item_log[5] : '아무것도 획득하지 못했습니다'; ?></strong>...</span>
                <?php if(isset($item_log[5]) && $item_log[5]) { ?><span>(토큰 보유 제한 초과)</span><?php } ?>
            </p>
        </div>
        <?php
        break;

    case 'P': /* 아이템 선물 */
        $p_it_id = isset($item_log[1]) ? $item_log[1] : '';
        $p_it_name = isset($item_log[2]) ? (string)$item_log[2] : '';
        $p_re_name = isset($item_log[4]) ? (string)$item_log[4] : '';
        $p_it = ($p_it_id !== '' && function_exists('get_item')) ? get_item($p_it_id) : array();
        if (!is_array($p_it)) $p_it = array();
        $p_img = function_exists('get_item_img') ? get_item_img($p_it_id) : (isset($p_it['it_img']) ? $p_it['it_img'] : '');
        if (!is_string($p_img)) $p_img = '';
        $p_content = isset($p_it['it_content']) ? (string)$p_it['it_content'] : '';
        ?>
        <div class="log-action-box data-item">
            <?php if ($p_img !== '') { ?>
            <em><img src="<?php echo htmlspecialchars($p_img, ENT_QUOTES); ?>" /></em>
            <?php } ?>
            <p>
                <span><strong><?php echo htmlspecialchars($p_re_name); ?></strong>에게 <strong><?php echo htmlspecialchars($p_it_name); ?></strong><?php echo j($p_it_name !== '' ? $p_it_name : '아이템', '을'); ?> 선물했습니다!</span>
            </p>
            <?php if ($p_content !== '') { ?>
            <p style="opacity:.8; font-style:italic;"><?php echo $p_content; ?></p>
            <?php } ?>
        </div>
        <?php
        break;

    default:
        /* 다른 모듈이 추가한 타입 — item_log_render_extra 필터로 위임.
           반환값이 비어있지 않을 때만 출력한다. */
        if (function_exists('mdl_apply_filters')) {
            $extra = mdl_apply_filters('item_log_render_extra', '', $item_log);
            if (is_string($extra) && $extra !== '') {
                echo $extra;
            }
        }
        break;
}
