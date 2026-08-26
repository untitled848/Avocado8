<?php
/**
 * bbs_action_map — 사용 훅 선언
 */
if (!defined('_GNUBOARD_')) exit;

return array(
    'uses' => array(
        'bbs_write_form_action_options',
        'bbs_write_form_action_panels',
        'bbs_comment_form_action_options',
        'bbs_custom_action',
        'bbs_view_row_extra',
        'bbs_enqueue_assets',
        'list_top',
        'write_after',
        'comment_after',
        'character_create_after',
    ),
);
