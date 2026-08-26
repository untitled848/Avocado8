<?php
if (!defined('_GNUBOARD_')) exit;

return array(
    'name'           => 'bbs_action_search',
    'title'          => '탐색 액션',
    'description'    => '게시판 글쓰기/코멘트시 "탐색" 액션을 제공하고, 탐색 결과로 아이템을 획득합니다.',
    'version'        => '1.0.0',
    'author'         => 'K',
    'board_scope'    => true,
    'board_default'  => 'off',
    'scopes'         => array('write', 'comment'),
    'admin_menu'     => true,
    'module_type'       => 'search',  // 그룹내 단독 활성화
    'requires_hooks' => array(
        'bbs_write_form_action_options',
        'bbs_comment_form_action_options',
        'bbs_custom_action',
        'bbs_view_row_extra',
    ),

    'admin_menus' => array(
        '002' => array(
            array('002100', '탐색 설정', G5_MDL_ADMIN_URL.'/module.php?m=bbs_action_search&p=setting', 'wb_setting'),
        ),
    ),
);
