<?php
/**
 * bbs_action_combine — 사용 훅 선언
 */
if (!defined('_GNUBOARD_')) exit;

return array(
    'uses' => array(
        'bbs_write_form_action_options',
        'bbs_write_form_action_panels',
        'bbs_comment_form_action_options',
        'bbs_comment_form_extra',
        'bbs_enqueue_assets',
        'bbs_custom_action',
        'bbs_view_row_extra',
        'character_create_after',
    ),
);
