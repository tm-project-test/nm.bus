<?php

namespace NM\Bus;

use Bitrix\Main\Entity;

class ReceiversTable extends Entity\DataManager
{
    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'b_nm_bus_receivers';
    }

    public static function getMap()
    {
        return [
            'ID' => [
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true
            ],
            'NAME' => [
                'data_type' => 'string',
                'title' => 'Название получателя',
                'required' => true
            ],
            'URL' => [
                'data_type' => 'string',
                'title' => 'Адрес получателя',
                'required' => true
            ]
        ];
    }
}