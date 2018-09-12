<?php
/**
 * Файл предназначен для проверки наличия сообщений в шине, которые необходимо переслать получателям
 */

$_SERVER['DOCUMENT_ROOT'] = dirname(dirname(dirname(dirname(__DIR__))));

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('CHK_EVENT', true);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if(CModule::IncludeModule('nm.bus')){
    $queue = new \NM\Bus\Queue();
    $queue->checkBus();
}