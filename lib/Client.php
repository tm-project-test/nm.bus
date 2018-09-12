<?php

namespace NM\Bus;

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Client
{
    private $queue;

    private $arSentData = [];
    private $receiverHostName = '';
    private $receiverIncludeModule = '';
    private $receiverHandlerClassName = '';
    private $receiverHandlerMethodName = '';
    private $receiverHandlerFunction = '';
    private $receiverHandlerEventName = '';
    private $receiverEventName = '';
    private $receiverObjectId = '';

    /**
     * Устанавливает получателя, которому будет доставлено сообщение
     *
     * @param string $receiverHostName Hostname получателя сообщения
     * @return $this
     */
    public function setReceiver($receiverHostName)
    {
        $this->receiverHostName = $receiverHostName;

        return $this;
    }

    /**
     * @return string Возвращает получателя, которому необходимо доставить сообщение
     */
    public function getReceiver()
    {
        return $this->receiverHostName;
    }

    /**
     * Устанавливает данные, которые будут отправлены получателю
     *
     * @param array $arSentData Отправляемые данные в формате многомерного массива
     * @return $this
     */
    public function setSentData($arSentData)
    {
        $this->arSentData = $arSentData;

        return $this;
    }

    /**
     * @return array Данные, которые будут отправлены получателю
     */
    public function getSentData()
    {
        return $this->arSentData;
    }

    /**
     * Определяет какой метод какого класса будет вызван на стороне получателя при получении сообщения
     * В данный метод при получении будет передано значение поля $arSentData
     *
     * @param string $receiverHandlerClassName Название класса
     * @param string $receiverHandlerMethodName Название статичного метода класса
     * @return $this
     */
    public function setReceiverHandlerClassMethod($receiverHandlerClassName, $receiverHandlerMethodName)
    {
        $this->receiverHandlerClassName = $receiverHandlerClassName;
        $this->receiverHandlerMethodName = $receiverHandlerMethodName;

        return $this;
    }

    /**
     * @return string Класс, метод которого необходимо вызвать на стороне получателя при получении сообщения
     */
    public function getReceiverHandlerClass()
    {
        return $this->receiverHandlerClassName;
    }

    /**
     * @return string Класс, метод которого необходимо вызвать на стороне получателя при получении сообщения
     */
    public function getReceiverHandlerMethod()
    {
        return $this->receiverHandlerMethodName;
    }

    /**
     * Определяет название функции, которая будет запущена на стороне получателя в момент получения
     * сообщения. В данную функцию будет передано значение поля $arSentData
     *
     * @param string $receiverHandlerFunction Название функции
     * @return $this
     */
    public function setReceiverHandlerFunction($receiverHandlerFunction)
    {
        $this->receiverHandlerFunction = $receiverHandlerFunction;

        return $this;
    }

    /**
     * @return string Название функции, запускаемой на стороне получателя
     */
    public function getReceiverHandlerFunction()
    {
        return $this->receiverHandlerFunction;
    }

    /**
     * Определяет название события, которое будет сгенерированно на стороне получателя в момент получения сообщения
     * Каждому подписчику на данное событие будет передано значение поля $arSentData
     *
     * @param string $receiverHandlerEventName Название события, которое будет сгенерированно
     * @return $this
     */
    public function setReceiverHandlerListener($receiverHandlerEventName)
    {
        $this->receiverHandlerEventName = $receiverHandlerEventName;

        return $this;
    }

    /**
     * @return string Название события, генерируемого на стороне получателя
     */
    public function getReceiverHandlerListener()
    {
        return $this->receiverHandlerEventName;
    }

    /**
     * Определяет модуль, который необходимо подключить на стороне получателя до запуска любого из обработчиков
     *
     * @param string $moduleId Идентификатор модуля
     * @return $this
     */
    public function setReceiverIncludeModule($moduleId)
    {
        $this->receiverIncludeModule = $moduleId;

        return $this;
    }

    /**
     * @return string Идентификатор модуля, подключаемого до обработки поступившего сообщения
     */
    public function getReceiverIncludeModule()
    {
        return $this->receiverIncludeModule;
    }

    /**
     * @param string $eventName Устанавливает название события, по которому производится передача
     * @return $this
     */
    public function setEventName($eventName)
    {
        $this->receiverEventName = $eventName;

        return $this;
    }

    /**
     * @param integer $iblockId Устанавливает ID инфоблока, по которому производится передача
     * @return $this
     */
    public function setObjectId($iblockId)
    {
        $this->receiverObjectId = $iblockId;

        return $this;
    }

    /**
     * @return string Название события, по которому производится передача
     */
    public function getReceiverEventName()
    {
        return $this->receiverEventName;
    }

    /**
     * @return string ID инфоблока, по которому производится передача
     */
    public function getReceiverObjectId()
    {
        return $this->receiverObjectId;
    }

    /**
     * Осуществляет попытку добавления текущего сообщения в очередь сообщений
     */

    /**
     * @param array Данные, которые будут отправлены получателю
     */
    public function send(array $arSentData = [])
    {
        if(count($arSentData)) {
            $this->arSentData = $arSentData;
        }

        if(!$this->queue){
            $this->queue = new Queue();
        }

        $this->queue->add($this);
    }

    /**
     * @param integer $iblockId ID инфоблока
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function checkIblockInSendObjects($iblockId)
    {
        $res = ObjectsTable::getList([
            'filter' => [
                'OBJECT' => (int) $iblockId
            ]
        ]);

        if($arObject = $res->fetch()){
            return true;
        }

        return false;
    }

    /**
     * @param integer $elementId ID отправляемого элемента
     */
    public static function sendObject($elementId)
    {
        $sender = new ObjectSender($elementId);
        $sender->send();
    }
}