<?php

namespace NM\Bus;

class ChangeElementHandler {
    /**
     * @param $arFields
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function onAfterIblockElementUpdate($arFields)
    {
        if(
            !defined('NM_BUS_UPDATE_FROM_BUS')
            &&
            isset($arFields['IBLOCK_ID'], $arFields['ID'])
            &&
            Client::checkIblockInSendObjects($arFields['IBLOCK_ID'])
        ) {
            Client::sendObject($arFields['ID']);
        }
    }

    /**
     * Генерация уникальных ID элементов
     *
     * @param $arFields
     */
    public static function OnIBlockElementAdd(&$arFields)
    {
        if (!isset($arFields['FROM_BUS'])) {
            $arFields['ID'] = \NM\Bus::getNextIBlockElementIDFromBus();
        }
    }
}