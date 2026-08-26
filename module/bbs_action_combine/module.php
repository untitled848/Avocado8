<?php
if (!defined('_GNUBOARD_')) exit;

return array(
    'name'           => 'bbs_action_combine',
    'title'          => '조합 액션',
    'description'    => '게시판 글쓰기/코멘트시 "조합" 액션을 제공하고, 레시피 기반 아이템 조합을 수행합니다.',
    'version'        => '1.0.0',
    'author'         => 'K',
    'board_scope'    => true,
    'board_default'  => 'off',
    'scopes'         => array('write', 'comment'),
    'admin_menu'     => true,
    'requires_hooks' => array(
        'bbs_write_form_action_options',
        'bbs_write_form_action_panels',
        'bbs_comment_form_action_options',
        'bbs_comment_form_extra',
        'bbs_custom_action',
        'bbs_view_row_extra',
    ),
    'module_type'       => 'combine',  // 그룹내 단독 활성화
    'admin_menus' => array(
        '002' => array(
            array('002200', '조합 설정', G5_MDL_ADMIN_URL.'/module.php?m=bbs_action_combine&p=setting', 'wb_setting'),
        ),
    ),
);
