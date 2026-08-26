<?php
if (!defined('_GNUBOARD_')) exit;

return array(
    'name'           => 'bbs_item_use',
    'title'          => '게시판 아이템 사용',
    'description'    => '글 작성 시 아이템 사용. mmb 원글은 사용/선물, mmb 댓글은 사용만. 타임라인은 사용/선물.',
    'version'        => '1.0.0',
    'author'         => 'K',
    'board_scope'    => true,
    'board_default'  => 'off',
    'scopes'         => array('write', 'comment'),
    'module_type'       => 'item_bbs',  // 그룹내 단독 활성화
    'requires_hooks' => array(
        'write_after',
        'comment_after',
        'bbs_write_form_after_action',
        'bbs_comment_form_after_action',
        'bbs_enqueue_assets',
    ),
);
