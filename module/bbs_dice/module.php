<?php
if (!defined('_GNUBOARD_')) exit;

return array(
    'name'           => 'bbs_dice',
    'title'          => '주사위',
    'description'    => '게시판 코멘트/글 작성시 주사위를 굴릴 수 있습니다.',
    'version'        => '1.0.0',
    'author'         => 'K',
    'board_scope'    => true,
    'board_default'  => 'off',
    'scopes'         => array('write', 'comment'),
    'requires_hooks' => array(
        'bbs_write_form_options',
        'bbs_comment_form_options',
        'bbs_custom_action',
        'bbs_view_row_extra',
    ),
    'module_type'       => 'dice',  // 그룹내 단독 활성화
    'admin_menus'    => array(
        '002' => array(
            array('002300', '주사위 설정', G5_MDL_ADMIN_URL.'/module.php?m=bbs_dice&p=dice_form', 'cube'),
        ),
    ),
);
