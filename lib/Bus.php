<?php

namespace NM;

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Bus
{
    private static $client, $server;

    public static function client()
    {
        if(!self::$client){
            self::$client = new Bus\Client();
        }

        return self::$client;
    }

    public function server()
    {
        if(!self::$server){
            self::$server = new Bus\Server();
        }

        return self::$server;
    }

    public static function getEventRecipients($eventName)
    {
        $result = [];

        $res = \Custom\ESB\Server\CESBServerHandlersTable::getList([
            'select' => [
                'EVENT', 'PORTAL_URL'
            ],
            'filter' => [
                'EVENT' => $eventName
            ]
        ]);

        while($arEvent = $res->fetch()){
            $result[] = $arEvent;
        }

        return $result;
    }

    public static function getObjectRecipients($objectId)
    {
        $result = [];

        $res = Bus\ObjectsHandlersTable::getList([
            'select' => [
                'OBJECT', 'PORTAL_URL'
            ],
            'filter' => [
                'OBJECT' => $objectId
            ]
        ]);

        while($arEvent = $res->fetch()){
            $result[] = $arEvent;
        }

        return $result;
    }

    public static function getNextIBlockElementIDFromBus()
    {
        global $DB;

        if(\CModule::IncludeModule('nm.bus')){
            $settings = new \NM\Bus\Settings();
            $idPrefix = $settings->getOption('bus_id_prefix');

            $lastRes = $DB->Query('SELECT `ID` FROM `b_iblock_element` ORDER BY ID DESC LIMIT 1')->Fetch();

            if(strpos($lastRes['ID'], $idPrefix) === false){
                return $idPrefix . ($lastRes['ID']+2);
            }

            return ($lastRes['ID']+2);
        }

        return null;
    }
}