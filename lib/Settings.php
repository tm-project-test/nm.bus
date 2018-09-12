<?php
namespace NM\Bus;

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Settings {

    const MODULE_ID = 'nm.bus';

    private $arModuleOption = [];

    /**
     * Описание полей настроек модуля
     *
     * @return array
     */
    public function getFieldsMap()
    {
        return [
            [
                [
                    'ID'        => 'bus_is_bus',
                    'TITLE'     => 'Портал является Интеграционной шиной',
                    'NAME'      => 'bus_is_bus',
                    'FIELD'     => 'checkbox',
                    'DEFAULT'   => '',
                    'STYLE'     => 'style="width: 250px;"'
                ],
                [
                    'ID'        => 'bus_url',
                    'TITLE'     => 'URL адрес интеграционной шины',
                    'NAME'      => 'bus_url',
                    'FIELD'     => 'text',
                    'DEFAULT'   => '',
                    'STYLE'     => 'style="width: 100%;"'
                ],
                [
                    'ID'        => 'bus_id_prefix',
                    'TITLE'     => 'Префикс идентификаторов сущностей',
                    'NAME'      => 'bus_id_prefix',
                    'FIELD'     => 'text',
                    'DEFAULT'   => '',
                    'STYLE'     => 'style="width: 250px;"',
                    'BUTTONS'   => [
                        'ID' => 'getBusId',
                        'TITLE' => 'Получить ID на портале ИШ'
                    ]
                ]
            ]
        ];
    }

    /**
     * Возвращает текущие настройки модуля включая описание
     */
    public function getSettings()
    {
        $setting = $this->getFieldsMap();

        // Выставляем значения полей в соотвествии с сохраненными ранее.
        // Если сохраненных нет, то выставляем занчения по-умолчанию
        $options = \COption::GetOptionString(self::MODULE_ID, "options");
        if ($options) {
            $options = unserialize($options);
        }

        foreach ($setting as $K => $V) {
            foreach ($V as $k => $v) {
                if (isset($options[$v["ID"]])) {
                    $setting[$K][$k]["VALUE"] = $options[$v["NAME"]];
                } else {
                    $setting[$K][$k]["VALUE"] = $v["DEFAULT"];
                }
            }
        }

        return $setting;
    }

    /**
     * Возвращает текущие настройки модуля
     */
    public function getOptions()
    {
        $this->arModuleOption = [];
        $options = $this->getSettings();
        foreach ($options as $option) {
            foreach ($option as $v) {
                $this->arModuleOption[$v["NAME"]] = $v["VALUE"];
            }
        }

        return $this->arModuleOption;
    }

    /**
     * Вернет значение параметра модуля по его ключу
     *
     * @param $key
     *
     * @return mixed
     */
    public function getOption($key)
    {
        if (!isset($this->arModuleOption[$key])) {
            $this->getOptions();
        }

        if (isset($this->arModuleOption[$key])) {
            return $this->arModuleOption[$key];
        }

        return null;
    }

    /**
     * Установит новые параметры модуля
     *
     * @param $newOptions
     *
     * @return bool
     */
    public static function setOptions($newOptions)
    {
        return \COption::SetOptionString(self::MODULE_ID, "options", serialize($newOptions));
    }

    /**
     * Удалит параметры модуля
     *
     * @return bool
     */
    public function removeOptions()
    {
        \COption::RemoveOption(self::MODULE_ID);
        return true;
    }
}