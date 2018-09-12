<?php

namespace NM\Bus;

use Bitrix\Main\Entity;

class ObjectsHandlersTable extends Entity\DataManager
{
    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'b_nm_objects_handlers';
    }

    public static function getMap()
    {
        return [
            'ID' => [
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true
            ],
            'PORTAL_URL' => [
                'data_type' => 'string',
                'title' => 'Адрес получателя',
                'required' => true
            ],
            'OBJECT' => [
                'data_type' => 'integer',
                'title' => 'Объект',
                'required' => true
            ]
        ];
    }
}