<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class nm_bus extends CModule
{
    public $MODULE_ID = 'nm.bus';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_GROUP_RIGHTS = 'Y';

    public function __construct()
    {
        $arModuleVersion = array();
        include __DIR__.'/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];

        $this->PARTNER_NAME = 'Notamedia';
        $this->PARTNER_URI = 'http://nota.media';

        $this->MODULE_NAME = Loc::getMessage('NM_BUS_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('NM_BUS_MODULE_DESCRIPTION');
    }

    public function InstallEvents()
    {
        global $DB;

        $this->dropTables();

        $DB->Query('
            CREATE TABLE b_esb_client_queue
            (
                ID			                    INT(11)		NOT NULL auto_increment,
                BUS_MESSAGE_ID					INT(11)     default 0,					/* ID сообщения в шине */
                TARGET_ENTITY_ID				INT(11)     default 0,					/* ID сущности в адресате */
                COUNTER_TRY						INT(11)     default 0,					/* кол-во попыток отправок */
                DATE_COMPLETED					DATETIME	NOT NULL,                   /* дата завершения задачи */
                DATE_STARTED					DATETIME	NOT NULL,                   /* дата добавления задачи */
                PROCESS_COMPLETED				INT(11)     NOT NULL default 0,         /* Задача не была завершена */
                PROCESS_STARTED					INT(11)     NOT NULL default 0,         /* Задача не была запущена */
                EMERGENCY_STOP					INT(11)     NOT NULL default 0,         /* Задача не была аварийно остановлена */ 
                CONNECTION_ERROR				varchar(2048),
                CONNECTION_RESULT				varchar(2048),
                CONNECTION_STATUS				varchar(2048),
                CONNECTION_HEADERS				varchar(2048),
                TYPE							varchar(255), 
                OBJECT							varchar(255), 
                EVENT							varchar(255),
                VALUE							longtext,                
                DIRECTION						text, /* направление OUTGOING | INCOMING  */
                PRIMARY KEY(ID)
            );
        ');

        $DB->Query('
            CREATE TABLE b_esb_server_queue /* Очередь сообщений*/
            (
                ID			                    INT(11)		NOT NULL auto_increment,
                COUNTER_TRY						INT(11)     default 0,				/* кол-во попыток отправок */                
                DATE_COMPLETED					DATETIME	NOT NULL,               /* дата завершения задачи */
                DATE_STARTED					DATETIME	NOT NULL,               /* дата добавления задачи */
                PROCESS_COMPLETED				INT(11)     NOT NULL default 0,     /* Задача не была завершена */
                PROCESS_STARTED					INT(11)     NOT NULL default 0,     /* Задача не была запущена */
                EMERGENCY_STOP					INT(11)     NOT NULL default 0,     /* Задача не была аварийно остановлена */
                TYPE							varchar(255), 
                EVENT							varchar(255),
                OBJECT							varchar(255),
                VALUE							longtext,
                CONNECTION_ERROR				varchar(2048),
                CONNECTION_RESULT				varchar(2048),
                CONNECTION_STATUS				varchar(2048),
                CONNECTION_HEADERS				varchar(2048),
                PRIMARY KEY(ID)
            );
        ');

        $DB->Query('
            CREATE TABLE b_esb_server_handlers /* Подписавшиеся на события порталы */
            (
                ID			                    INT(11)	NOT NULL auto_increment, 
                EVENT							varchar(255), 
                PORTAL_URL						varchar(255),
                PRIMARY KEY(ID)
            );
        ');

        $DB->Query('
            CREATE TABLE b_nm_objects_handlers /* Подписавшиеся на объекты порталы */
            (
                ID			                    int(11) NOT NULL auto_increment, 
                OBJECT							int(11), 
                PORTAL_URL						varchar(255),
                PRIMARY KEY(ID)
            );
        ');

        $DB->Query('
            CREATE TABLE b_nm_bus_objects /* Объекты для отправки */
            (
                ID			                    int(11) NOT NULL auto_increment, 
                OBJECT							int(11),
                PRIMARY KEY(ID)
            );
        ');

        $DB->Query('
            CREATE TABLE `b_nm_bus_receivers` (
                `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `NAME` varchar(255) DEFAULT NULL,
                `URL` varchar(255) DEFAULT NULL,
                PRIMARY KEY (`ID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');

        $DB->Query('
            ALTER TABLE `b_iblock_element` CHANGE `ID` `ID` BIGINT(32) NOT NULL AUTO_INCREMENT;
        ');

        RegisterModuleDependences('iblock', 'OnAfterIBlockElementAdd', $this->MODULE_ID, '\\NM\\Bus\\ChangeElementHandler', 'onAfterIblockElementUpdate');
        RegisterModuleDependences('iblock', 'OnAfterIBlockElementUpdate', $this->MODULE_ID, '\\NM\\Bus\\ChangeElementHandler', 'onAfterIblockElementUpdate');
        RegisterModuleDependences('iblock', 'OnIBlockElementAdd', $this->MODULE_ID, '\\NM\\Bus\\ChangeElementHandler', 'onIBlockElementAdd');

        return true;
    }

    public function UnInstallEvents()
    {
        $this->dropTables();

        UnRegisterModuleDependences('iblock', 'OnAfterIBlockElementAdd', $this->MODULE_ID, '\\NM\\Bus\\ChangeElementHandler', 'onAfterIblockElementUpdate');
        UnRegisterModuleDependences('iblock', 'OnAfterIBlockElementUpdate', $this->MODULE_ID, '\\NM\\Bus\\ChangeElementHandler', 'onAfterIblockElementUpdate');
        UnRegisterModuleDependences('iblock', 'OnIBlockElementAdd', $this->MODULE_ID, '\\NM\\Bus\\ChangeElementHandler', 'onIBlockElementAdd');

        return true;
    }

    public function InstallFiles()
    {
        \CopyDirFiles(
            "{$_SERVER['DOCUMENT_ROOT']}/local/modules/nm.bus/install/admin",
            "{$_SERVER['DOCUMENT_ROOT']}/bitrix/admin",
            true
        );

        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/iblock/classes/general/iblockelement.php';
        if($fileContent = file_get_contents($filePath)){
            if(strpos($fileContent, '$arFields["FROM_BUS"]') === false) {
                $fileContent = str_replace([
                    'unset($arFields["ID"]);',
                    'ExecuteModuleEventEx($arEvent, array($arFields))'
                ], [
                    'if(!isset($arFields["FROM_BUS"])) { unset($arFields["ID"]); }',
                    'ExecuteModuleEventEx($arEvent, array(&$arFields))'
                ], $fileContent);
                file_put_contents($filePath, $fileContent);
            }
        }
    }

    public function UnInstallFiles()
    {
        \DeleteDirFiles(
            "{$_SERVER['DOCUMENT_ROOT']}/local/modules/nm.bus/install/admin/",
            "{$_SERVER['DOCUMENT_ROOT']}/bitrix/admin"
        );
    }

    public function DoInstall()
    {
        $this->InstallFiles();
        $this->InstallEvents();
        RegisterModule($this->MODULE_ID);
    }

    public function DoUninstall()
    {
        UnRegisterModule($this->MODULE_ID);
        $this->UnInstallEvents();
        $this->UnInstallFiles();
    }

    public function dropTables()
    {
        global $DB;

        $DB->Query('
            DROP TABLE if exists b_esb_client_queue;
        ');

        $DB->Query('
            DROP TABLE if exists b_esb_server_queue;
        ');

        $DB->Query('
            DROP TABLE if exists b_esb_server_handlers;
        ');

        $DB->Query('
            DROP TABLE if exists b_nm_objects_handlers;
        ');

        $DB->Query('
            DROP TABLE if exists b_nm_bus_objects;
        ');

        $DB->Query('
            DROP TABLE if exists b_nm_bus_receivers;
        ');
    }
}

