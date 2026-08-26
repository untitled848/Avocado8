<?php
/**
 * bbs_item_use — 사용 훅 선언
 */
if (!defined('_GNUBOARD_')) exit;

return array(
    'uses' => array(
        'write_after',
        'comment_after',
        'bbs_write_form_after_action',
        'bbs_comment_form_after_action',
        'bbs_enqueue_assets',
    ),
    // bbs/use.php 가 발사하는 훅들 — bbs_item_use 가 lazy-load 시키므로 자기 모듈 책임
    'fires' => array(
        'item_use_pre',
        'item_use_core',
        'item_use_post',
        'item_log_render',
    ),
);
