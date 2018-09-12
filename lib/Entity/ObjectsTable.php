<?php

namespace NM\Bus;

use Bitrix\Main\Entity;

class ObjectsTable extends Entity\DataManager
{
    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'b_nm_bus_objects';
    }

    public static function getMap()
    {
        return [
            'ID' => [
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true
            ],
            'OBJECT' => [
                'data_type' => 'integer',
                'title' => 'Объект',
                'required' => true
            ]
        ];
    }
}