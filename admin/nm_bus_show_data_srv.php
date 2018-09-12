<?php

/** @global CUser $USER */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/iblock/prolog.php';

define('MODULE_ID', 'nm.bus');

$APPLICATION->SetTitle('Данные сообщения #' . (int) $_GET['ID']);

$tabControl = new CAdminForm('nm_bus_message_add', [
    [
        'DIV' => 'edit1',
        'TAB' => 'Данные сообщения',
        'ICON' => 'iblock_element',
        'TITLE' => 'Данные, передаваемые в сообщении'
    ]
]);

$tabControl->Buttons(
    [
        'btnSave' => false,
        'btnApply' => false,
        'btnCancel' => false,
    ]
);

$tabControl->Begin();

$tabControl->BeginNextFormTab();

$tabControl->BeginCustomField('MESSAGE', 'MESSAGE', false);

global $DB;

$res = $DB->Query('SELECT * FROM `b_esb_server_queue` WHERE ID=' . (int) $_GET['ID']);
$arData = $res->Fetch();
?>
    <div style="background: #fff; border: 1px solid #c4ced2">
        <pre style="padding: 10px;"><?=json_encode(json_decode($arData['VALUE']), JSON_PRETTY_PRINT)?></pre>
    </div>
<?php
$tabControl->EndCustomField('MESSAGE');

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';


$tabControl->Show();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';