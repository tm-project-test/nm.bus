<?php
/**
 * Файл предназначен для проверки наличия входящих сообщений, инициирует обработку входящих сообщений
 */

$_SERVER['DOCUMENT_ROOT'] = dirname(dirname(dirname(dirname(__DIR__))));

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS',true);
define('CHK_EVENT', true);
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if(CModule::IncludeModule('nm.bus')){
    $queue = new \NM\Bus\Queue();
    $queue->checkIncoming();
}

require_once($_SERVER['DOCUMENT_ROOT'] .'/bitrix/modules/main/include/epilog_after.php');