<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

IncludeModuleLangFile(__FILE__);

if(!$back_url){
    $back_url = '/bitrix/admin/nm_bus_handlers.php?lang='.$lang;
}

$errors = '';

if(!$USER->IsAdmin() || !CModule::IncludeModule('nm.bus')){
	$APPLICATION->AuthForm('Доступ запрещен');
}

use Custom\ESB;
use NM\Bus\ReceiversTable;

$sTableID = 'b_esb_server_handlers';

// Если редактирование
if (isset($_REQUEST['ID']) && $_REQUEST['ID'] > 0) {
	$handler = Custom\ESB\Server\CESBServerHandlersTable::getById($_REQUEST['ID'])->fetch();

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
    ESB\Server\CESBServerHandlersTable::delete($handler['ID']);
	LocalRedirect($back_url);
}

// Сохранение
if(check_bitrix_sessid() && (strlen($save)>0 || strlen($apply)>0)){

    $arFields['EVENT'] = trim($EVENT);
    $arFields['PORTAL_URL'] = isset($RECEIVER) ? (int) $RECEIVER : false;

    if ($is_update_form){
        $ID = (int) $_REQUEST['ID'];
        $result = ESB\Server\CESBServerHandlersTable::update($ID, $arFields);
    } else {
        $result = ESB\Server\CESBServerHandlersTable::add($arFields);
        $ID = $result->getId();
    }

    if($result->isSuccess()){
        if (strlen($save)>0){
            LocalRedirect('/bitrix/admin/nm_bus_handlers.php?lang=' . LANGUAGE_ID);
        } else {
            LocalRedirect('/bitrix/admin/nm_bus_handlers_edit.php?ID=' .$ID. '&lang=' .LANGUAGE_ID. '&' .$tabControl->ActiveTabParam());
        }
    } else {
        $errors = $result->getErrorMessages();
    }

    foreach ($arFields as $k => $v) {
        $handler[$k] = $v;
    }
}

if(strlen($ID)>0) {
    $APPLICATION->SetTitle('Редактирование подписчика на событие');
} else {
    $APPLICATION->SetTitle('Добавление подписчика на событие');
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$aMenu = [
	[
		'TEXT' => 'Назад к списку',
		'TITLE' => 'Назад к списку',
		'LINK' => '/bitrix/admin/nm_bus_handlers.php?lang=' . LANGUAGE_ID,
		'ICON' => 'btn_list'
	]
];

$context = new CAdminContextMenu($aMenu);
$context->Show();

if(!empty($errors)){
	CAdminMessage::ShowMessage(implode("\n", $errors));
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

<form method="POST" id="form" name="form" action="/bitrix/admin/nm_bus_handlers_edit.php?lang=<?echo LANG?>">
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
    		<td width="40%"><label for="EVENT">Событие:</label></td>
    		<td width="60%">
                <input type="text" id="EVENT" name="EVENT"  value="<?=$handler['EVENT']?>" style="width: 220px;">
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