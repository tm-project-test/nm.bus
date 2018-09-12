<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/options.php');

if (!$USER->CanDoOperation('edit_other_settings')) {
    $APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
}

CJSCore::Init(['jquery']);

$moduleId = 'nm.bus';
CModule::IncludeModule($moduleId);

$objModule = new \NM\Bus\Settings();
$setting = $objModule->getSettings();

$aTabs = [
    0 => ["DIV" => "edit1", "TAB" => 'Интеграционная шина', "ICON" => "", 'Настройки интеграционной шины']
];

$tabControl = new CAdminTabControl("tabControl", $aTabs);

if (check_bitrix_sessid() && (isset($_POST["Apply"]) || isset($_POST["RestoreDefaults"]))) {
    if (isset($_POST["RestoreDefaults"])) {
        $objModule->removeOptions();
    } else {
        $newOptions = [];

        foreach ($setting as $V) {
            foreach ($V as $v) {
                if ($v["FIELD"] == 'checkbox') {
                    $newOptions[$v["NAME"]] = isset($_POST[$v["NAME"]]) ? "Y" : "N";
                } else {
                    $newOptions[$v["NAME"]] = isset($_POST[$v["NAME"]]) ? $_POST[$v["NAME"]] : $v["DEFAULT"];
                }
            }
        }
        $objModule::setOptions($newOptions);
    }

    LocalRedirect($APPLICATION->GetCurPage() . "?mid=" . urlencode($moduleId) . "&lang=" . urlencode(LANGUAGE_ID) . "&" . $tabControl->ActiveTabParam() . ($_REQUEST["siteTabControl_active_tab"] <> '' ? "&siteTabControl_active_tab=" . urlencode($_REQUEST["siteTabControl_active_tab"]) : ''));
}
?>

<script type="text/javascript">
    $(document).ready(function(){
        $('#getBusId').click(function(){
            <?$busId = $objModule->getOption('bus_id_prefix');?>
            $(this).prev().val('<?=$busId ? $busId : mt_rand(10000, 99999)?>');
            return false;
        })
    });
</script>
<form method="post"
      action="<? echo $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($moduleId) ?>&amp;lang=<?= urlencode(LANGUAGE_ID) ?>">
    <?= bitrix_sessid_post() ?>
    <?
    $tabControl->Begin();

    foreach ($aTabs AS $key => $tab):
        $tabControl->BeginNextTab();
            foreach ($setting[$key] AS $field):
                $style = (isset($field['STYLE']) ? $field['STYLE'] : ''); ?>
                <?if ($field["HEADING"]):?>
                    <tr class="heading">
                        <td colspan="2"><?= $field["HEADING"] ?></td>
                    </tr>
                <?endif?>
                <tr>
                    <td width="30%" class="field-name">
                        <?= $field["TITLE"] ?>:
                    </td>
                    <td width="70%" style="padding-left: 7px;">
                        <?
                        if ($field["FIELD"] == "checkbox"):?>
                            <?
                            $checked = $field["VALUE"] == "Y" ? ' checked="checked"' : '' ?>
                            <input id="<?= $field['ID']; ?>" type="checkbox" name="<?= $field["NAME"] ?>" value="Y" <?= $checked ?>>
                        <? elseif ($field["FIELD"] == "text"): ?>
                            <input type="text" id="<?= $field['ID']; ?>" name="<?= $field["NAME"] ?>" value="<?= $field["VALUE"] ?>" <?= $style; ?>>
                            <?php if (isset($field['BUTTONS'])) :
                                $btn = $field['BUTTONS'];
                                $dataHtml = (!empty($btn['DATA']) ? $btn['DATA'] : '');
                                ?>
                                <input id="<?= $btn['ID']; ?>" value="<?= $btn["TITLE"]; ?>" name="" title=""
                                       class="adm-btn-save js_btn_request" style="margin-left:5px"
                                       <?= $dataHtml; ?> type="submit">
                            <?php endif; ?>
                        <? elseif ($field["FIELD"] == "textarea"): ?>
                            <textarea rows="3" cols="25" name="<?= $field["ID"] ?>" <?= $style; ?>><?= $field["VALUE"] ?></textarea>
                        <? endif ?>
                    </td>
                </tr>
                <?if ($field["MESSAGE"]):?>
                    <tr>
                        <td align="center" colspan="2">
                            <div class="adm-info-message-wrap" align="center">
                                <div class="adm-info-message"><?= $field["MESSAGE"] ?></div>
                            </div>
                        </td>
                    </tr>
                <?endif?>
            <?endforeach?>
    <?endforeach?>

    <?$tabControl->Buttons(); ?>
    <input type="hidden" name="siteTabControl_active_tab"
           value="<?= htmlspecialcharsbx($_REQUEST["siteTabControl_active_tab"]) ?>">
    <input type="submit" name="Apply" class="adm-btn-save" value="<?=GetMessage('MAIN_OPT_APPLY') ?>"
           title="<?=GetMessage('MAIN_OPT_APPLY_TITLE') ?>">
    <?=bitrix_sessid_post(); ?>
    <?$tabControl->End(); ?>

    <div style="display: block;width: 800px;">
        <div id="request_result_error" style="color:red"></div>
        <div><pre id="request_result"></pre></div>
    </div>
</form>