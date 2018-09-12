<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use NM\Bus\ObjectsHandlersTable;
use NM\Bus\ReceiversTable;

$APPLICATION->SetTitle('Подписчики на объекты');

IncludeModuleLangFile(__FILE__);

if (!$USER->IsAdmin() || !CModule::IncludeModule('nm.bus') || !CModule::IncludeModule('fileman')) {
    $APPLICATION->AuthForm('Доступ запрещен');
}

$site = $_REQUEST['site'];
$site = CFileMan::__CheckSite($site);

$sTableID = 'b_nm_objects_handlers';

$oSort = new CAdminSorting($sTableID, 'ID', 'asc');
$lAdmin = new CAdminList($sTableID, $oSort);

if($arID = $lAdmin->GroupAction()){

    if($_REQUEST['action_target'] === 'selected'){
        $terms = ObjectsHandlersTable::getList();
        while($term = $terms->fetch()){
            $arID[] = $term['ID'];
        }
    }

    foreach($arID as $ID){

        if(!(int) $ID){
            continue;
        }

        switch($_REQUEST['action']){
            case 'delete':
                if(ObjectsHandlersTable::delete($ID)){
                    ?>
                    <div class='adm-info-message-wrap adm-info-message-green'>
                        <div class='adm-info-message'>
                            <div class='adm-info-message-title'>Успешно</div>
                            Записи успешно удалены
                            <div class='adm-info-message-icon'></div>
                        </div>
                    </div>
                    <?php
                } else {
                    $lAdmin->AddGroupError('При удалении записей возникла ошибка', $ID);
                }
                break;
        }
    }
}


$lAdmin->AddHeaders([
    [
        'id' => 'ID',
        'content' => 'ID',
        'sort'=>'id',
        'default'=>true
    ],
    [
        'id' => 'OBJECT',
        'content' => 'Объект',
        'default' => true
    ],
    [
        'id' => 'PORTAL_URL',
        'content' => 'Адрес получателя',
        'default' => true
    ]
]);

$rsData = ObjectsHandlersTable::getList([
    'select' => [
        'ID',
        'OBJECT',
        'PORTAL_URL',
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

$allReceivers = [];
$rsRecData = ReceiversTable::getList([
    'select' => ['ID', 'NAME', 'URL'],
    'order' => ['NAME' => 'ASC']
]);
while($arReceiver = $rsRecData->fetch()){
    $allReceivers[$arReceiver['ID']] = $arReceiver['NAME'];
}

$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();

$lAdmin->NavText($rsData->GetNavPrint(GetMessage('PAGES')));

while($arRes = $rsData->NavNext(true)){

    $row =& $lAdmin->AddRow($arRes['ID'], $arRes, 'nm_bus_objects_edit.php?lang=' . LANG.'&amp;ID='.$arRes['ID'], GetMessage('MAIN_ADMIN_MENU_EDIT'));

    $row->AddViewField('ID', $arRes['ID']);
    $row->AddViewField('OBJECT', '[' . $allIblockTypes[$allIblocks[$arRes['OBJECT']]['IBLOCK_TYPE_ID']] .'] '. $allIblocks[$arRes['OBJECT']]['NAME']);
    $row->AddViewField('PORTAL_URL', $allReceivers[$arRes['PORTAL_URL']]);
}

$lAdmin->AddAdminContextMenu([
    [
        'TEXT' => 'Добавить подписчика',
        'LINK' =>'/bitrix/admin/nm_bus_objects_edit.php?lang=' . LANG,
        'TITLE' => 'Добавить подписчика',
        'ICON' => 'btn_new',
    ],
], false, true);


$lAdmin->AddFooter(
    [
        ['title' => GetMessage('MAIN_ADMIN_LIST_SELECTED'), 'value'=>$rsData->SelectedRowsCount()],
        ['counter' => true, 'title' => GetMessage('MAIN_ADMIN_LIST_CHECKED'), 'value'=>'0']
    ]
);


$lAdmin->AddGroupActionTable([
    'delete' => 'Удалить все'
]);

$lAdmin->CheckListMode();

if ($_REQUEST['mode'] === 'list'){
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_js.php';
} else {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
}

$lAdmin->CheckListMode();

$lAdmin->DisplayList();

if ($_REQUEST['mode'] === 'list') {
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_js.php';
} else {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
}
