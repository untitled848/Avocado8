<?php
if (!defined('_GNUBOARD_')) exit;

return array(
    'name'           => 'bbs_action_map',
    'title'          => '지역 탐색',
    'description'    => '지역 이동·이벤트·몬스터 공략 (AvocadoEdition.MapManager).',
    'version'        => '1.0.0',
    'author'         => 'K',
    'board_scope'    => true,
    'board_default'  => 'off',
    'scopes'         => array('write'),
    'requires_hooks' => array(
        'bbs_write_form_action_options',
        'bbs_write_form_action_panels',
        'bbs_comment_form_action_options',
        'bbs_custom_action',
        'bbs_view_row_extra',
        'bbs_enqueue_assets',
        'list_top',
    ),
    'admin_menus'    => array(
        'auto' => array(
            array('000', '지역 탐색', G5_MDL_ADMIN_URL.'/module.php?m=bbs_action_map&p=setting'),
            array('100', '탐색 설정', G5_MDL_ADMIN_URL.'/module.php?m=bbs_action_map&p=setting'),
            array('200', '맵 관리', G5_MDL_ADMIN_URL.'/module.php?m=bbs_action_map&p=map_list'),
            array('201', '이벤트 관리', G5_MDL_ADMIN_URL.'/module.php?m=bbs_action_map&p=event_list'),
            array('202', '캐릭터 위치', G5_MDL_ADMIN_URL.'/module.php?m=bbs_action_map&p=member_list'),
        ),
    ),
);
