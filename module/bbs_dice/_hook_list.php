<?php
/**
 * bbs_dice — 사용 훅 선언
 *
 * uses : 이 모듈이 add_action / add_filter 로 거는 훅
 * fires: 이 모듈이 do_action / apply_filters 로 발사하는 훅 (없으면 생략)
 */
if (!defined('_GNUBOARD_')) exit;

return array(
    'uses' => array(
        'bbs_write_form_options',
        'bbs_comment_form_options',
        'bbs_custom_action',
        'bbs_view_row_extra',
    ),
);
