<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Custom\ESB\Server\CESBServerHandlersTable;
use NM\Bus\ReceiversTable;

$APPLICATION->SetTitle('Подписчики на события');

IncludeModuleLangFile(__FILE__);

if (!$USER->IsAdmin() || !CModule::IncludeModule('nm.bus') || !CModule::IncludeModule('fileman')) {
    $APPLICATION->AuthForm('Доступ запрещен');
}

$site = $_REQUEST['site'];
$site = CFileMan::__CheckSite($site);

$sTableID = 'b_esb_server_handlers';

$oSort = new CAdminSorting($sTableID, 'ID', 'asc');
$lAdmin = new CAdminList($sTableID, $oSort);

if($arID = $lAdmin->GroupAction()){

    if($_REQUEST['action_target'] === 'selected'){
        $terms = CESBServerHandlersTable::getList();
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
                if(CESBServerHandlersTable::delete($ID)){
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
        'id' => 'EVENT',
        'content' => 'Событие',
        'default' => true
    ],
    [
        'id' => 'PORTAL_URL',
        'content' => 'Адрес получателя',
        'default' => true
    ]
]);

$rsData = CESBServerHandlersTable::getList([
    'select' => [
        'ID',
        'EVENT',
        'PORTAL_URL',
    ],
    'order' => [
        strtoupper($by) => strtoupper($order)
    ]
]);

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

    $row =& $lAdmin->AddRow($arRes['ID'], $arRes, 'nm_bus_handlers_edit.php?lang=' . LANG.'&amp;ID='.$arRes['ID'], GetMessage('MAIN_ADMIN_MENU_EDIT'));

    $row->AddViewField('ID', $arRes['ID']);
    $row->AddViewField('EVENT', $arRes['EVENT']);
    $row->AddViewField('PORTAL_URL', $allReceivers[$arRes['PORTAL_URL']]);
}

$lAdmin->AddAdminContextMenu([
    [
        'TEXT' => 'Добавить подписчика',
        'LINK' =>'/bitrix/admin/nm_bus_handlers_edit.php?lang=' . LANG,
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
?>

<?php
if ($_REQUEST['mode'] === 'list') {
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin_js.php';
} else {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
}
