<?php

namespace NM\Bus;

use \Bitrix\Main\Application;
use \Bitrix\Main\Localization\Loc;
use \Custom\ESB\Client\CESBClient;
use \Custom\ESB\Client\Queue as ClientQueue;
use \Custom\ESB\Server\Queue as ServerQueue;

Loc::loadMessages(__FILE__);

class Queue
{
    /**
     * Добавляет новое сообщение в очередь сообщений
     *
     * @param Client $client
     */
    public function add(Client $client)
    {
        /**
         * Использование старой шины обмена
         * @todo Изменить логику постановки в очередь
         */

        $ESB = new CESBClient();

        $ESB->push(
            [
                'TYPE' => 'CustomExample',
                'EVENT' => $client->getReceiverHandlerListener(),
                'OBJECT' => $client->getReceiverObjectId(),
                'VALUE' => [
                    'RECEIVER'  => $client->getReceiver(),
                    'MODULE'    => $client->getReceiverIncludeModule(),
                    'CLASS'     => $client->getReceiverHandlerClass(),
                    'METHOD'    => $client->getReceiverHandlerMethod(),
                    'FUNCTION'  => $client->getReceiverHandlerFunction(),
                    'EVENT'     => $client->getReceiverHandlerListener(),
                    'OBJECT'    => $client->getReceiverObjectId(),
                    'DATA'      => $client->getSentData()
                ]
            ]
        );
    }

    /**
     * Проверяет наличие в очереди не отправленных сообщений на стороне сервера,
     * осуществляет попытку их отправки
     */
    public function checkBus()
    {
        // Использование старой проверки очереди сообщений

        while (ob_get_level()) {
            ob_end_flush();
        }

        @set_time_limit(0);

        $queue = new ServerQueue();
        $queue->Execute();
    }

    /**
     * Проверяет наличие в очереди не отправленных сообщений на стороне клиента,
     * осуществляет попытку их отправки
     */
    public function checkOutgoing()
    {
        // Использование старой логики проверки очереди

        while (ob_get_level()){
            ob_end_flush();
        }

        @set_time_limit(0);

        $queue = new ClientQueue();
        $queue->Execute();
    }

    /**
     * Проверяет наличие в очереди не обработанных входящих сообщений, осуществляет
     * их обработку
     */
    public function checkIncoming()
    {
        // Используется старая логика обработки входящих сообщений

        while (ob_get_level()) {
            ob_end_flush();
        }

        @set_time_limit(0);

        $queue = new \Custom\ESB\Client\QueueIncoming();
        $queue->Execute();
    }

    /**
     * Добавляет полученные сообщения в локальную очередь обработки
     */
    public function addReceivedMessage()
    {
        // Используется старая логика добавления новых полученных сообщений

        @set_time_limit(0);

        \Bitrix\Main\Diag\Debug::dumpToFile('-----------------');
        \Bitrix\Main\Diag\Debug::dumpToFile($_POST);

        $request = Application::getInstance()->getContext()->getRequest();

        $FIELDS = \json_decode($request->getInput(), true);
        $TYPE = $FIELDS['TYPE'];
        $EVENT = $FIELDS['EVENT'];
        $OBJECT = $FIELDS['OBJECT'];
        $VALUE = json_encode($FIELDS['VALUE']);

        $result = \Custom\ESB\Client\CESBClientQueueTable::add([
            'PROCESS_STARTED' => 0, //отправка сообщения не была запущена
            'PROCESS_COMPLETED' => 0, //отправка сообщения не была завершена
            'EMERGENCY_STOP' => 0, //отправка сообщения не была аварийно остановлена
            'TYPE' => $TYPE,
            'EVENT' => $EVENT,
            'OBJECT' => $OBJECT,
            'VALUE' => $VALUE,
            'DIRECTION' => 'INCOMING'
        ]);

        if ($result->isSuccess()) {
            die(json_encode(array('result' => 'success')));
        }

        die(json_encode(array('result' => 'error', 'errors' => implode(', ', $result->getErrors()))));
    }

    /**
     * Добавляет полученные сообщения в локальную очередь для обработки их шиной
     */
    public function addMessageToBus()
    {
        @set_time_limit(0);

        \Bitrix\Main\Diag\Debug::dumpToFile('-----------------');
        \Bitrix\Main\Diag\Debug::dumpToFile($_POST);

        $request = Application::getInstance()->getContext()->getRequest();

        $FIELDS = json_decode($request->getInput(), true);
        $TYPE = $FIELDS['TYPE'];
        $EVENT = $FIELDS['EVENT'];
        $OBJECT = $FIELDS['OBJECT'];
        $VALUE = json_encode($FIELDS['VALUE']);

        $result = \Custom\ESB\Server\CESBServerQueueTable::add([
            'PROCESS_STARTED' => 0, //отправка сообщения не была запущена
            'PROCESS_COMPLETED' => 0, //отправка сообщения не была завершена
            'EMERGENCY_STOP' => 0, //отправка сообщения не была аварийно остановлена
            'TYPE' => $TYPE,
            'EVENT' => $EVENT,
            'OBJECT' => $OBJECT,
            'VALUE' => $VALUE
        ]);

        if ($result->isSuccess()) {
            echo json_encode(array('result' => 'success'));
        } else {
            echo json_encode(array('result' => 'error', 'errors' => implode(', ', $result->getErrors())));
        }
    }

    /**
     * Осуществляет запуск обработчиков принятого сообщения
     *
     * @param array $message Принятое сообщение
     */
    public function processMessage($message)
    {
        // Попытка подключения модуля, переданного в сообщении
        if($message['MODULE']){
            \CModule::IncludeModule($message['MODULE']);
        }

        // Попытка вызова метода класса, переданного в сообщении
        if($message['CLASS'] && $message['METHOD'] && method_exists($message['CLASS'], $message['METHOD'])){
            call_user_func($message['CLASS'] .'::'. $message['METHOD'], $message['DATA']);
        }

        // Попытка вызова функции, переданной в сообщении
        if($message['FUNCTION'] && function_exists($message['FUNCTION'])){
            call_user_func($message['FUNCTION'], $message['DATA']);
        }

        // Попытка отработки подписчиков на события
        if($message['MODULE'] && $message['EVENT']){

            $event = new \Bitrix\Main\Event(
                $message['MODULE'],
                $message['EVENT'],
                [
                    $message['DATA']
                ]
            );

            $event->send();
        }

        // Попытка отработки объектов, переданных в сообщении
        if($message['OBJECT']){
            $this->upsertObjectElements($message);
        }
    }

    public function upsertObjectElements($message)
    {
        if(!defined('NM_BUS_UPDATE_FROM_BUS')) {
            define('NM_BUS_UPDATE_FROM_BUS', true);
        }

        foreach($message['DATA'] as $item){

            $item['FROM_BUS'] = 'Y';

            $el = new \CIBlockElement();

            $res = \CIBlockElement::GetByID($item['ID']);

            if($arItem = $res->Fetch()){
                $el->Update($arItem['ID'], $item);
            } else {
                $el->Add($item);
            }
        }
    }
}