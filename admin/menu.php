<?php

global $USER;

if( $USER->IsAdmin() ) {
	$aMenu = array(
		'parent_menu' => 'global_menu_services',
		'section' => 'nm_bus',
		'sort' => 300,
		'text' => 'Интеграционная шина',
		'title' => 'Интеграционная шина',
		'url' => 'esb_server_admin.php?lang='.LANGUAGE_ID,
		'icon' => 'update_menu_icon',
		'page_icon' => 'update_menu_icon',
		'items_id' => 'menu_nm_bus',

        'items' => [
			[
				'text' 	=> 'Исходящие сообщения',
				'title' => 'sender_trig_menu_icon',
				'url' 	=> '/bitrix/admin/nm_bus_order.php?lang=' . LANGUAGE_ID,
				'icon' 	=> 'sender_menu_icon',
				'page_icon' => 'sender_menu_icon',
				'more_url'  => []
			],
            [
				'text' 	=> 'Входящие сообщения',
				'title' => 'sender_trig_menu_icon',
				'url' 	=> '/bitrix/admin/nm_bus_order_in.php?lang=' . LANGUAGE_ID,
				'icon' 	=> 'subscribe_menu_icon',
				'page_icon' => 'subscribe_menu_icon',
				'more_url'  => []
			],
            [
				'text' 	=> 'Объекты для отправки',
				'title' => 'sender_trig_menu_icon',
				'url' 	=> '/bitrix/admin/nm_bus_send_objects.php?lang=' . LANGUAGE_ID,
				'icon' 	=> 'update_marketplace',
				'page_icon' => 'update_marketplace',
				'more_url'  => [
				    '/bitrix/admin/nm_bus_send_objects_edit.php'
                ]
			],
            [
				'text' 	=> 'Отправить сообщение',
				'title' => 'sender_trig_menu_icon',
				'url' 	=> '/bitrix/admin/nm_bus_message_add.php?lang=' . LANGUAGE_ID,
				'icon' 	=> 'update_menu_icon_partner',
				'page_icon' => 'update_menu_icon_partner',
				'more_url'  => [
				    '/bitrix/admin/nm_bus_message_add.php'
                ]
			]
		]
	);

    if(CModule::IncludeModule('nm.bus')){
        $objModule = new \NM\Bus\Settings();

        if($objModule->getOption('bus_is_bus') === 'Y'){

            $aMenu['items'][] = [
                'text' 		=> 'ИШ: Справочник получателей',
                'title' 	=> 'ИШ: Справочник получателей',
                'url'  		=> '/bitrix/admin/nm_bus_receivers.php?lang=' . LANGUAGE_ID,
                'icon' 		=> 'iblock_menu_icon_types',
                'page_icon' => 'iblock_menu_icon_types',
                'more_url'  => [
                    '/bitrix/admin/nm_bus_receivers_edit.php'
                ],
            ];

            $aMenu['items'][] = [
                'text' 		=> 'ИШ: Очередь передачи',
                'title' 	=> 'ИШ: Очередь передачи',
                'url'  		=> '/bitrix/admin/nm_bus_reorder.php?lang=' . LANGUAGE_ID,
                'icon' 		=> 'sender_trig_menu_icon',
                'page_icon' => 'sender_trig_menu_icon',
                'more_url'  => [],
            ];

            $aMenu['items'][] = [
                'text' 		=> 'ИШ: Подписчики на события',
                'title' 	=> 'ИШ: Подписчики на события',
                'url'  		=> '/bitrix/admin/nm_bus_handlers.php?lang=' . LANGUAGE_ID,
                'icon' 		=> 'controller_menu_icon',
                'page_icon' => 'controller_menu_icon',
                'more_url'  => [
                    '/bitrix/admin/nm_bus_handlers_edit.php'
                ],
            ];

            $aMenu['items'][] = [
                'text' 		=> 'ИШ: Подписчики на объекты',
                'title' 	=> 'ИШ: Подписчики на объекты',
                'url'  		=> '/bitrix/admin/nm_bus_objects.php?lang=' . LANGUAGE_ID,
                'icon' 		=> 'controller_menu_icon',
                'page_icon' => 'controller_menu_icon',
                'more_url'  => [
                    '/bitrix/admin/nm_bus_objects_edit.php'
                ],
            ];
        }
    }

	return $aMenu;
}

return false;





