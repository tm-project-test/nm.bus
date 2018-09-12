<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

$APPLICATION->SetTitle('Исходящие сообщения');

IncludeModuleLangFile(__FILE__);

if (!$USER->IsAdmin() || !CModule::IncludeModule('nm.bus') || !CModule::IncludeModule('fileman')){
    $APPLICATION->AuthForm('Доступ запрещен');
}

$site = $_REQUEST['site'];
$site = CFileMan::__CheckSite($site);

use Custom\ESB\Client\CESBClientQueueTable;

$sTableID = 'b_esb_client_queue';

$oSort = new CAdminSorting($sTableID, 'ID', 'asc');

$lAdmin = new CAdminList($sTableID, $oSort);

if($arID = $lAdmin->GroupAction()){

    if($_REQUEST['action_target'] === 'selected'){

        $terms = CESBClientQueueTable::getList();

        while($term = $terms->fetch()){
            $arID[] = $term['ID'];
        }
    }

    foreach($arID as $ID) {

        if(!(int) $ID){
            continue;
        }

        switch($_REQUEST['action']){
            case 'delete':

                if(CESBClientQueueTable::delete($ID)){
                    ?>
                    <div class="adm-info-message-wrap adm-info-message-green">
                        <div class="adm-info-message">
                            <div class="adm-info-message-title"><?=GetMessage('ESB_CLIENT_TERM_REMOVED_TITLE')?></div>
                            <?=GetMessage('ESB_CLIENT_TERM_REMOVED_MSG')?>
                            <div class="adm-info-message-icon"></div>
                        </div>
                    </div>
                    <?php
                } else {
                    $lAdmin->AddGroupError(GetMessage('IBLOCK_TYPE_ADMIN_ERR_DEL'), $ID);
                }
                break;
        }
    }
}

$lAdmin->AddHeaders([
    [
        'id' => 'ID',
        'content' => 'ID',
        'sort' => 'id',
        'default' => true
    ],
    [
        'id' => 'DATE_STARTED',
        'content' => 'Старт',
        'sort' => 'date_started',
        'default' => true,
        'align' => 'center'
    ],
    [
        'id' => 'DATE_COMPLETED',
        'content' => 'Финиш',
        'sort' => 'date_completed',
        'default' => true
    ],
    [
        'id' => 'PROCESS_STARTED',
        'content' => 'Начато',
        'sort' => 'process_started',
        'default' => true
    ],
    [
        'id' => 'PROCESS_COMPLETED',
        'content' => 'Завершено',
        'sort' => 'process_completed',
        'default' => true
    ],
    [
        'id' => 'EMERGENCY_STOP',
        'content' => 'Завершено аварийно',
        'sort' => 'emergency_stop',
        'default' => true
    ],
    [
        'id' => 'CONNECTION_ERROR',
        'content' => 'Ошибка соединения',
        'default' => true
    ],
    [
        'id' => 'CONNECTION_RESULT',
        'content' => 'Результат передачи',
        'default' => true
    ],
    [
        'id' => 'CONNECTION_STATUS',
        'content' => 'Статус ответа',
        'default' => true
    ],
    [
        'id' => 'COUNTER_TRY',
        'content' => 'Количество попыток',
        'default' => true
    ],
    [
        'id' => 'EVENT',
        'content' => 'Событие',
        'default' => true
    ],
    [
        'id' => 'OBJECT',
        'content' => 'Объект',
        'default' => true
    ],
    [
        'id' => 'VALUE',
        'content' => 'Данные',
        'default' => true
    ]
]);

$rsData = CESBClientQueueTable::getList([
    'filter' => [
        'DIRECTION' => 'OUTGOING'
    ],
    'select' => [
        'ID',
        'DATE_COMPLETED',
        'DATE_STARTED',
        'PROCESS_COMPLETED',
        'PROCESS_STARTED',
        'EMERGENCY_STOP',
        'CONNECTION_ERROR',
        'CONNECTION_RESULT',
        'CONNECTION_STATUS',
        'CONNECTION_HEADERS',
        'DIRECTION',
        'COUNTER_TRY',
        'TYPE',
        'EVENT',
        'OBJECT',
        'VALUE',
    ],
    'order' => [
        strtoupper($by) => strtoupper($order)
    ]
]);


$allIblockTypes = [];
$resIblockTypes = CIBlockType::GetList(['SORT' => 'ASC']);
while($arIblockType = $resIblockTypes->Fetch()){
    if($arIBType = CIBlockType::GetByIDLang($arIblockType['ID'], LANG)){
        $allIblockTypes[$arIblockType['ID']] = $arIBType['NAME'];
    }
}

$allIblocks = [];
$res = CIBlock::GetList(['SORT' => 'ASC'], []);
while($arIblock = $res->Fetch()){
    $allIblocks[$arIblock['ID']] = $arIblock;
}


$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();

if(!function_exists('humanBytes')) {
    function humanBytes($size)
    {
        $filesizename = array(' байт', ' КБ', ' МБ', ' ГБ', ' ТБ', ' ПБ', ' ЕБ', ' ZB', ' YB');
        return $size ? round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $filesizename[$i] : '0 байт';
    }
}

$lAdmin->NavText($rsData->GetNavPrint(GetMessage('PAGES')));
while($arRes = $rsData->NavNext(true))
{
    $row =& $lAdmin->AddRow($arRes['ID'], $arRes, 'esb_client_edit.php?lang='.LANG.'&amp;ID='.$arRes['ID'], GetMessage('MAIN_ADMIN_MENU_EDIT'));

    $error = str_replace(["\r", "\n", ' '], ['', '', ''], $arRes['CONNECTION_ERROR']) === 'Array()' ? 'Нет' : $arRes['CONNECTION_ERROR'];
    $result = json_decode($arRes['CONNECTION_RESULT'], true);
    $result = isset($result['result']) && $result['result'] === 'success' ? 'Успешно' : $arRes['CONNECTION_RESULT'];

    $row->AddViewField('ID', $arRes['ID']);
    $row->AddViewField('DIRECTION', $arRes['DIRECTION'] === 'OUTGOING' ? 'Исходящее' : 'Входящее');
    $row->AddViewField('DATE_COMPLETED', $arRes['DATE_COMPLETED']);
    $row->AddViewField('DATE_STARTED', $arRes['DATE_STARTED']);
    $row->AddViewField('PROCESS_COMPLETED', ($arRes['PROCESS_COMPLETED'] === '1' ? 'Да' : 'Нет'));
    $row->AddViewField('PROCESS_STARTED', ($arRes['PROCESS_STARTED'] === '1' ? 'Да' : 'Нет'));
    $row->AddViewField('EMERGENCY_STOP', ($arRes['EMERGENCY_STOP'] === '1' ? 'Да' : 'Нет'));
    $row->AddViewField('CONNECTION_ERROR', $error);
    $row->AddViewField('CONNECTION_RESULT', $result);
    $row->AddViewField('CONNECTION_STATUS', $arRes['CONNECTION_STATUS']);
    $row->AddViewField(
        'OBJECT',
        isset($allIblocks[$arRes['OBJECT']]) ? '[' . $allIblockTypes[$allIblocks[$arRes['OBJECT']]['IBLOCK_TYPE_ID']] .'] '. $allIblocks[$arRes['OBJECT']]['NAME'] : ''
    );
    $row->AddViewField('COUNTER_TRY', $arRes['COUNTER_TRY'] > 0 || $arRes['PROCESS_STARTED'] !== '1' ? $arRes['COUNTER_TRY'] : 1);
    $row->AddViewField('VALUE', '<a href="/bitrix/admin/nm_bus_show_data.php?ID='. $arRes['ID'] .'" title="Просмотр данных">'. humanBytes(strlen($arRes['VALUE'])) . '</a>');
}

$lAdmin->AddFooter(
    [
        [
            'title' => GetMessage('MAIN_ADMIN_LIST_SELECTED'),
            'value' => $rsData->SelectedRowsCount()
        ],
        [
            'counter' => true,
            'title' => GetMessage('MAIN_ADMIN_LIST_CHECKED'),
            'value' => '0'
        ]
    ]
);

$lAdmin->AddGroupActionTable(array(
    'delete' => GetMessage('MAIN_ADMIN_LIST_DELETE')
));

$lAdmin->CheckListMode();


if ($_REQUEST['mode'] === 'list'){
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_js.php';
} else {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
}

$lAdmin->CheckListMode();

$lAdmin->DisplayList();

if ($_REQUEST['mode'] === 'list') {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_js.php';
} else {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
}