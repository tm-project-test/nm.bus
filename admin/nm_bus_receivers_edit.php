<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

IncludeModuleLangFile(__FILE__);

if(!$back_url){
    $back_url = '/bitrix/admin/nm_bus_receivers.php?lang='.$lang;
}

$errors = '';

if(!$USER->IsAdmin() || !CModule::IncludeModule('nm.bus')){
    $APPLICATION->AuthForm('Доступ запрещен');
}

use NM\Bus\ReceiversTable;

$sTableID = 'b_nm_bus_objects';

// Если редактирование
if (isset($_REQUEST['ID']) && $_REQUEST['ID'] > 0) {
    $handler = ReceiversTable::getById($_REQUEST['ID'])->fetch();

    if (!empty($handler))
    {
        $is_update_form = true;
        $is_create_form = false;
    }
}

$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => 'Отправляемый объект',
        'ICON' => 'iblock_type',
        'TITLE' => 'Информация об отправляемом объекте',
    ]
];

$tabControl = new CAdminTabControl('tabControl', $aTabs);

// Удаление
if ($is_update_form && isset($_REQUEST['action']) && $_REQUEST['action'] === 'delete' && check_bitrix_sessid())
{
    ReceiversTable::delete($handler['ID']);
    LocalRedirect($back_url);
}

// Сохранение
if(check_bitrix_sessid() && (strlen($save)>0 || strlen($apply)>0)){

    $arFields['NAME'] = trim($NAME);
    $arFields['URL'] = trim($URL);

    if ($is_update_form){
        $ID = (int) $_REQUEST['ID'];
        $result = ReceiversTable::update($ID, $arFields);
    } else {
        $result = ReceiversTable::add($arFields);
        $ID = $result->getId();
    }

    if($result->isSuccess()){
        if (strlen($save)>0){
            LocalRedirect('/bitrix/admin/nm_bus_receivers.php?lang=' . LANGUAGE_ID);
        } else {
            LocalRedirect('/bitrix/admin/nm_bus_receivers_edit.php?ID=' .$ID. '&lang=' .LANGUAGE_ID. '&' .$tabControl->ActiveTabParam());
        }
    } else {
        $errors = $result->getErrorMessages();
    }

    foreach ($arFields as $k => $v) {
        $handler[$k] = $v;
    }
}

if(strlen($ID)>0) {
    $APPLICATION->SetTitle('Редактирование получателя');
} else {
    $APPLICATION->SetTitle('Добавление получателя');
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$aMenu = [
    [
        'TEXT' => 'Назад к списку',
        'TITLE' => 'Назад к списку',
        'LINK' => '/bitrix/admin/nm_bus_receivers.php?lang=' . LANGUAGE_ID,
        'ICON' => 'btn_list'
    ]
];

$context = new CAdminContextMenu($aMenu);
$context->Show();

if(!empty($errors)){
    CAdminMessage::ShowMessage(implode("\n", $errors));
}

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
?>

    <form method="POST" id="form" name="form" action="/bitrix/admin/nm_bus_receivers_edit.php?lang=<?echo LANG?>">
        <?=bitrix_sessid_post()?>

        <?echo GetFilterHiddens('find_');?>

        <input type="hidden" name="Update" value="Y">
        <input type="hidden" name="ID" value="<?=$ID?>">

        <?if(strlen($back_url)>0):?><input type="hidden" name="back_url" value="<?=htmlspecialchars($back_url)?>"><?endif?>

        <?php
        $tabControl->Begin();
        $tabControl->BeginNextTab();
        ?>
        <tr>
            <td width="40%"><label for="OBJECT">Название получателя:</label></td>
            <td width="60%">
                <input type="text" id="NAME" name="NAME" style="width: 220px;" value="<?=$handler['NAME']?>">
            </td>
        </tr>
        <tr>
            <td width="40%"><label for="OBJECT">URL получателя:</label></td>
            <td width="60%">
                <input type="text" id="URL" name="URL" style="width: 220px;" value="<?=$handler['URL']?>">
            </td>
        </tr>
        <?php
        $tabControl->Buttons(array('disabled' => false, 'back_url' => $back_url));
        $tabControl->End();
        ?>
    </form>

<?/*echo BeginNote();?>
                    <?=GetMessage('ESB_HANDLERS_DESCRIPTION')?>
<?echo EndNote();*/?>

<?require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php'?>