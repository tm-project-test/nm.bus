<?php

/** @global CUser $USER */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/iblock/prolog.php';

define('MODULE_ID', 'nm.bus');

$APPLICATION->SetTitle('Отправить сообщение');

$tabControl = new CAdminForm('nm_bus_message_add', [
    [
        'DIV' => 'edit1',
        'TAB' => 'Отправка сообщения',
        'ICON' => 'iblock_element',
        'TITLE' => 'Отправка сообщения'
    ]
]);

$tabControl->Buttons(
    [
        'btnSave' => false,
        'btnApply' => false,
        'btnCancel' => false,
    ],
    '<input type="submit" name="save" value="Отправить сообщение" title="Отправить сообщение" class="adm-btn-save">'
);

$tabControl->Begin();

$tabControl->BeginNextFormTab();


$allIblocks = [
    '' => '- выберите инфоблок -'
];
$res = CIBlock::GetList(['SORT' => 'ASC'], []);
while($arIblock = $res->Fetch()){
    $allIblocks[$arIblock['ID']] = $arIblock['NAME'];
}
$tabControl->AddDropDownField('OBJECT', 'Отправляемый объект:', false, $allIblocks, isset($_POST['OBJECT']) ? $_POST['OBJECT'] : false, ['width' => 150]);

$arFields = [
    'RECEIVER' => 'Получатель',
    'MODULE' => 'Подключаемый модуль',
    'CLASS' => 'Вызываемый класс',
    'METHOD' => 'Вызываемый метод',
    'FUNCTION' => 'Вызываемая функция',
    'EVENT' => 'Генерируемое событие'
];

foreach($arFields as $code => $title){
    $tabControl->AddEditField($code, $title . ':', false, ['size' => 40], isset($_POST[$code]) ? $_POST[$code] : '');
}

$tabControl->AddTextField('DATA', 'Данные (JSON):', isset($_POST['DATA']) ? $_POST['DATA'] : '', ['cols' => 80, 'rows' => 20], false);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

if(isset($error) && !empty($error)) {
    CAdminMessage::ShowOldStyleError($error);
}

if(isset($_POST['save'])){
    CAdminMessage::ShowNote('Сообщение успешно отправлено');

    CModule::IncludeModule('nm.bus');

    $client = \NM\Bus::client();

    if($_POST['RECEIVER']){
        $client->setReceiver($_POST['RECEIVER']);
    }

    if($_POST['OBJECT']){
        $client->setObjectId($_POST['OBJECT']);
    }

    if($_POST['MODULE']){
        $client->setReceiverIncludeModule($_POST['MODULE']);
    }

    if($_POST['CLASS']){
        $client->setReceiverHandlerClassMethod($_POST['CLASS'], $_POST['METHOD']);
    }

    if($_POST['FUNCTION']){
        $client->setReceiverHandlerFunction($_POST['']);
    }

    if($_POST['EVENT']){
        $client->setReceiverHandlerListener($_POST['EVENT']);
    }

    $client->setSentData(\json_decode($_POST['DATA'], true))->send();
}

$tabControl->Show();

/*
echo BeginNote() . 'Сообщение' . EndNote();
*/


require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';