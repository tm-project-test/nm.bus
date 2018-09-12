<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

IncludeModuleLangFile(__FILE__);

if(!$back_url){
    $back_url = '/bitrix/admin/nm_bus_objects.php?lang='.$lang;
}

$errors = '';

if(!$USER->IsAdmin() || !CModule::IncludeModule('nm.bus')){
    $APPLICATION->AuthForm('Доступ запрещен');
}

use NM\Bus\ObjectsHandlersTable;
use NM\Bus\ReceiversTable;

$sTableID = 'b_nm_objects_handlers';

// Если редактирование
if (isset($_REQUEST['ID']) && $_REQUEST['ID'] > 0) {
    $handler = ObjectsHandlersTable::getById($_REQUEST['ID'])->fetch();

    if (!empty($handler))
    {
        $is_update_form = true;
        $is_create_form = false;
    }
}

$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => 'Подписчик на собитие',
        'ICON' => 'iblock_type',
        'TITLE' => 'Информация о подписчике на событие',
    ]
];

$tabControl = new CAdminTabControl('tabControl', $aTabs);

// Удаление
if ($is_update_form && isset($_REQUEST['action']) && $_REQUEST['action'] === 'delete' && check_bitrix_sessid())
{
    ObjectsHandlersTable::delete($handler['ID']);
    LocalRedirect($back_url);
}

// Сохранение
if(check_bitrix_sessid() && (strlen($save)>0 || strlen($apply)>0)){

    $arFields['OBJECT'] = trim($OBJECT);
    $arFields['PORTAL_URL'] = isset($RECEIVER) ? (int) $RECEIVER : false;


    if ($is_update_form){
        $ID = (int) $_REQUEST['ID'];
        $result = ObjectsHandlersTable::update($ID, $arFields);
    } else {
        $result = ObjectsHandlersTable::add($arFields);
        $ID = $result->getId();
    }

    if($result->isSuccess()){
        if (strlen($save)>0){
            LocalRedirect('/bitrix/admin/nm_bus_objects.php?lang=' . LANGUAGE_ID);
        } else {
            LocalRedirect('/bitrix/admin/nm_bus_objects_edit.php?ID=' .$ID. '&lang=' .LANGUAGE_ID. '&' .$tabControl->ActiveTabParam());
        }
    } else {
        $errors = $result->getErrorMessages();
    }

    foreach ($arFields as $k => $v) {
        $handler[$k] = $v;
    }
}

if(strlen($ID)>0) {
    $APPLICATION->SetTitle('Редактирование подписчика на объект');
} else {
    $APPLICATION->SetTitle('Добавление подписчика на объект');
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$aMenu = [
    [
        'TEXT' => 'Назад к списку',
        'TITLE' => 'Назад к списку',
        'LINK' => '/bitrix/admin/nm_bus_objects.php?lang=' . LANGUAGE_ID,
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


$allReceivers = [];
$rsData = ReceiversTable::getList([
    'select' => ['ID', 'NAME', 'URL'],
    'order' => ['NAME' => 'ASC']
]);
while($arReceiver = $rsData->fetch()){
    $allReceivers[$arReceiver['ID']] = $arReceiver['NAME'];
}
?>

    <form method="POST" id="form" name="form" action="/bitrix/admin/nm_bus_objects_edit.php?lang=<?echo LANG?>">
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
            <td width="40%"><label for="OBJECT">Объект:</label></td>
            <td width="60%">
                <select id="OBJECT" name="OBJECT" style="width: 220px;">
                    <option value="">- выберите инфоблок -</option>
                    <?foreach($allIblockTypes as $iblockType => $iblockTypeName):?>
                        <optgroup label="<?=$iblockTypeName?>">
                            <?foreach($allIblocks as $iblock):?>
                                <?if($iblock['IBLOCK_TYPE_ID'] === $iblockType):?>
                                    <option value="<?=$iblock['ID']?>"<?if((int) $iblock['ID'] === (int) $handler['OBJECT']):?> selected="selected"<?endif?>><?=$iblock['NAME']?></option>
                                <?endif?>
                            <?endforeach?>
                        </optgroup>
                    <?endforeach?>
                </select>
            </td>
        </tr>
        <tr>
            <td width="40%"><label for="RECEIVER">Получатель:</label></td>
            <td width="60%">
                <select id="RECEIVER" name="RECEIVER" style="width: 220px;">
                    <option value="">- выберите получателя -</option>
                    <?foreach($allReceivers as $receiverId => $receiver):?>
                        <option value="<?=$receiverId?>"<?if((int) $receiverId === (int) $handler['PORTAL_URL']):?> selected="selected"<?endif?>><?=$receiver?></option>
                    <?endforeach?>
                </select>
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