<?php
/**
 * bbs_action_search — 사용 훅 선언
 */
if (!defined('_GNUBOARD_')) exit;

return array(
    'uses' => array(
        'bbs_write_form_action_options',
        'bbs_comment_form_action_options',
        'bbs_custom_action',
        'bbs_view_row_extra',
        'character_create_after',
        'inventory_use_dispatch',
        'item_use_pre',
        'item_type_options',
    ),
);
