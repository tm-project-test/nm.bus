<?php

    namespace Custom\ESB\Server;

    use Bitrix\Main\Entity;
    use Bitrix\Main\Type\DateTime;
	use Bitrix\Main\Web\HttpClient;
    use NM\Bus\ReceiversTable;

    /**
    * Описание таблицы b_esb_server_handlers для ORM
    */
	class CESBServerHandlersTable extends Entity\DataManager
    {
        public static function getFilePath()
            {
            return __FILE__;
        }

        public static function getTableName()
        {
            return 'b_esb_server_handlers';
        }

        public static function getMap()
        {
            return array(
                'ID' => [
                    'data_type' => 'integer',
                    'primary' => true,
                    'autocomplete' => true
                ],
				'PORTAL_URL' => [
                    'data_type' => 'string',
                    'title' => 'Получатель',
                    'required' => true
                ],
				'EVENT' => [
                    'data_type' => 'string',
                    'title' => 'Событие',
                    'required' => true
                ]
            );
        }
    }
	
    /**
    * Описание таблицы b_esb_server_queue для ORM
    */
    class CESBServerQueueTable extends Entity\DataManager
    {
        public static function getFilePath()
            {
            return __FILE__;
        }

        public static function getTableName()
        {
            return 'b_esb_server_queue';
        }

        public static function getMap()
        {
            return array(
                'ID' => array(
                    'data_type' => 'integer',
                    'primary' => true,
                    'autocomplete' => true
                ),

				
                'DATE_COMPLETED' => array(              /*  */
                    'data_type' => 'datetime',
                ),
                'DATE_STARTED' => array(
                    'data_type' => 'datetime',          /*  */
                ),

				
				'COUNTER_TRY' => array(               /*  */
                    'data_type' => 'integer',
                ),
				
				
                'PROCESS_COMPLETED' => array(               /*  */
                    'data_type' => 'integer',
                    'required' => true,
                ),
				'PROCESS_STARTED' => array(               /*  */
                    'data_type' => 'integer',
                    'required' => true,
                ),
				'EMERGENCY_STOP' => array(               /*  */
                    'data_type' => 'integer',
                    'required' => true,
                ),
				
				
				/*'ERROR' => array(     
                    'data_type' => 'string',
                ),			
				'RESPONSE' => array(     
                    'data_type' => 'string',
                ),*/
				
				'CONNECTION_ERROR' => array(   
                    'data_type' => 'string',
                ),
                'CONNECTION_RESULT' => array(  
                    'data_type' => 'string',
                ),
                'CONNECTION_STATUS' => array(  
                    'data_type' => 'string',
                ),
                'CONNECTION_HEADERS' => array( 
                    'data_type' => 'string',
                ),				

				/*'PHP' => array(     
                    'data_type' => 'string',
                ),		 			
				'MESSAGE' => array(     
                    'data_type' => 'string',
                ),					
				'MESSAGE_TYPE' => array(     
                    'data_type' => 'string',
                ),

				'PORTAL' => array(     
                    'data_type' => 'string',
                ),*/
				'TYPE' => array(     
                    'data_type' => 'string',
                ),
                'OBJECT' => array(
                    'data_type' => 'string',
                ),
				'EVENT' => array(     
                    'data_type' => 'string',
                ),
				'VALUE' => array(     
                    'data_type' => 'string',
                ),
				
				
            );
        }
    }

    /**
    * Движок клиента
    */
	
class Queue
{
	var $operation_log;
	var $response;
	var $time_start; //время начала 
	var $task; 		 //код 
	
	var $connection_error;
	var $connection_result;
	var $connection_status;
	var $connection_headers;
	
	
	var $hlblock; 		 	// 
	var $entity; 		 	// 
	var $type; 		//порталы получатели 
	var $event; 		//порталы получатели 
	var $to_portals; 		//порталы получатели 
	var $max_task_executing_time; //максимальное время отведённое для выполнения одной задачи 
	var $max_session_executing_time;
    var $message;
    var $entity_item_id; //максимальное время отведённое на сессию (за одну сессию может выполняться несколько заданий из очереди)

	
	public function __construct()
	{
        $this->time_start = $this->GetMicroTime();//дата начала 
		$this->operation_log = array();
		$this->result = '';
		$this->type = '';
		$this->event = '';
		$this->to_portals = array();
		$this->response = '';

		$this->connection_error = '';
		$this->connection_result = '';
		$this->connection_status = '';
		$this->connection_headers = '';
		
		
		$this->max_task_executing_time = 50; //максимальное время отведённое для выполнения одной задачи 600 секунд
		//$this->max_task_executing_time = 600; //максимальное время отведённое для выполнения одной задачи 600 секунд
		$this->max_session_executing_time = 59; //сессия может длится максимум 59 секунд. Cron запускает скрипт раз в минуту, поэтому ставим ограничение в 59 секунд, чтобы уменьшить вероятность наложения нескольких сессий друг на друга
	}
	
	function GetMicroTime()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
	
	function GetExecutingMessage()//выбрать все выполняемые в данный момент задачи
	{
			$resData = '';
			$resData = CESBServerQueueTable::GetList(array(
					'select' => array( 'ID', 'DATE_COMPLETED', 'DATE_STARTED', 'PROCESS_COMPLETED', 'PROCESS_STARTED', 'EMERGENCY_STOP', /*'MESSAGE', 'MESSAGE_TYPE'*/),
					'filter' => array( 'PROCESS_STARTED' => '1', 'PROCESS_COMPLETED' => '0' ),
					'order'  => array( 'ID' => 'ASC' ),
					//'limit'  => '100',
			));

			while( $arItem = $resData->Fetch() ) {
					$result[] = $arItem;
			}
			
			//print_r($result); die();
			
			if( is_array($result) && count($result)>0 )
				return $result;
			else
				return false;
	}
	
	function ExecutingMessageEmergencyStop( $TASK )//аварийная остановка задачи, которая выполнялась слишком долго
	{
			$result = CESBServerQueueTable::update(
				 $TASK['ID'],
				 array(
							'EMERGENCY_STOP' => 1,
							'PROCESS_COMPLETED' => 1,
				 )
			);
			if ($result->isSuccess())
			{
				return true;
			}
			else
			{
				return false;
			}
	}
	
	function MessageExecutingTooLong( $TASK )//проверка - задача выполняется слишком долго?
	{
			$DATE_CURRENT = time();
			//print_r($TASK['DATE_STARTED']);
			$DATE_STARTED = $TASK['DATE_STARTED']->getTimestamp();
			
			$DATE_DELTA = $DATE_CURRENT - $DATE_STARTED;
											
																		echo "\n";
			print_r('DATE_STARTED: '); 	print_r($DATE_STARTED); 		echo "\n";
			print_r('DATE_CURRENT: '); 	print_r($DATE_CURRENT); 		echo "\n";
			print_r('DATE_DELTA: '); 	print_r($DATE_DELTA); 			echo "\n";
			
			if( $DATE_DELTA > $this->max_task_executing_time ) //если время выполнения превысило максимальное значение 
				return true;
			else
				return false;
	}

	function SetResult( $result )//устанавливает результат работы задачи (в объекте)
	{
		$this->result = $result;
	}
	
	function SaveResult()//сохраняет результат работы задачи (в БД)
	{
			$result = CESBServerQueueTable::update(
				 $this->entity_item_id,
				 [
                    'CONNECTION_ERROR'   => $this->connection_error,
                    'CONNECTION_RESULT'  => $this->connection_result,
                    'CONNECTION_STATUS'  => $this->connection_status,
                    'CONNECTION_HEADERS' => $this->connection_headers
				 ]
			);

			if ($result->isSuccess()){
				return true;
			} else {
				return false;
			}
	}
	
	function SetLOG( $log )//устанавливает лог выполнения задачи (в объекте)
	{
		$this->operation_log = $log;
	}
	
	function SaveLOG()//сохраняет лог выполнения задачи (в БД)
	{
			$time = time();


			$file_path = $_SERVER["DOCUMENT_ROOT"]."/upload/custom/log_queue/".date("Y",$time).'/'.date("m",$time).'/'.date("d",$time).'/'.date("Y.m.d H:i:s",$time).".txt";
            \Bitrix\Main\IO\File::putFileContents( $file_path, print_r( $this->operation_log, true ) );

			/*$result = $this->entity_data_class::update(
				 $this->entity_item_id,//$arItem['ID'],
				 array(
							'LOG' => \CFile::MakeFileArray($file_path),
				 )
			);
			if ($result->isSuccess())
			{
				return true;
			}
			else
			{
				return false;
				//echo(' ОШИБКА ' . implode(', ', $result->getErrors()) . "\n");
			}*/
	}

	function AddLOG( $method, $arguments, $result )//добавляет запись в лог выполнения задачи (в объекте)
	{
		/*if( 
				(is_string($arguments) || is_array($arguments)) &&
				(is_string($result) || is_array($result)) 
		)*/
		$this->operation_log[] = array('method'=>$method, 'arguments'=>$arguments, 'result'=>$result);
	}
	
	function ShowLOG()
	{
		print_r( $this->operation_log );
	}
	
	function SentLOG( $mail_to )//отправка лога на почту
	{
		/* TODO: метод отправки лога на почту */
		//$this->operation_log;
		//SendError( $this->operation_log );
	}
	
	function CompleteMessage()//завершение работы задачи
	{
	
			$result = CESBServerQueueTable::update(
				 $this->entity_item_id,//$arItem['ID'],
				 array(
							'PROCESS_COMPLETED' => 1,
							'DATE_COMPLETED' => (new DateTime()),
				 )
			);
			
			$this->SaveLOG();			
			$this->SaveResult();
			
			if ($result->isSuccess())
			{
				return true;
			}
			else
			{
				return false;
				//echo(' ОШИБКА ' . implode(', ', $result->getErrors()) . "\n");
			}
			
	}
	
	function SendMessage()//отправить задачу в шину
	{
			$result = CESBServerQueueTable::update(
				 $this->entity_item_id,
				 [
				     'PROCESS_STARTED' => 1,
                    'DATE_STARTED' => new DateTime()
				 ]
			);

			if( $result->isSuccess() ){

				if($this->message){

				    // Если определен получатель сообщения
				    if($this->message['VALUE']['RECEIVER']) {
                        $this->sendMessageToReciever($this->message, $this->message['VALUE']['RECEIVER']);
                    }

                    $allReceivers = [];
                    $rsData = ReceiversTable::getList(['select' => ['ID', 'NAME', 'URL']]);
                    while($arReceiver = $rsData->fetch()){
                        $allReceivers[$arReceiver['ID']] = $arReceiver;
                    }

                    // Если есть подписанные на событие
                    if($this->message['EVENT']){
                        foreach (\NM\Bus::getEventRecipients($this->message['EVENT']) as $event) {
                            $this->sendMessageToReciever($this->message, $allReceivers[$event['PORTAL_URL']]['URL']);
                        }
                    }

                    // Если есть подписанные на объект
                    if($this->message['OBJECT']){
                        foreach (\NM\Bus::getObjectRecipients($this->message['OBJECT']) as $object) {
                            $this->sendMessageToReciever($this->message, $allReceivers[$object['PORTAL_URL']]['URL']);
                        }
                    }
				}

				return true;

			} else {
				return false;
			}
	}

	function sendMessageToReciever($message, $reciever)
    {
        $httpClient = new HttpClient([
            'disableSslVerification' => true
        ]);

        $httpClient->setHeader('Content-Type', 'application/json', true);

        $httpClient->post(
            trim($reciever, '/') . '/local/modules/nm.bus/tools/receiver.php',
            json_encode($message)
        );

        $this->connection_error   = print_r($httpClient->getError(), true);
        $this->connection_result  = print_r($httpClient->getResult(), true);
        $this->connection_status  = print_r($httpClient->getStatus(), true);
        $this->connection_headers = print_r($httpClient->getHeaders()->toArray(), true);
    }
	
	function AddMessage($msg, $msg_type = 'JSON')//добавить сообщение
	{		
	
			/*if( $msg_type == 'JSON')
			{
				$msg_converted = json_encode( $msg );
				$msg_value_converted = json_encode( $msg['VALUE'] );
			}
			elseif( $msg_type == 'XML')
			{
				$msg_converted = xml( $msg );
				$msg_value_converted = xml( $msg['VALUE'] );
			}
			elseif( $msg_type == 'SERIALIZABLE')
			{
				$msg_converted = serialize( $msg );
				$msg_value_converted = serialize( $msg['VALUE'] );
			}
			else
			{
				$msg_converted = ( $msg );
				$msg_value_converted = ( $msg['VALUE'] );
			}
			
			echo '<pre>';
			print_r( $msg );
			echo '</pre>';
			*/
			$result = CESBServerQueueTable::add(
				 array(
							//'MESSAGE' => $msg_converted,
							//'MESSAGE_TYPE' => $msg_type,
							'PROCESS_STARTED'   => 0, //отправка сообщения не была запущена
							'PROCESS_COMPLETED' => 0, //отправка сообщения не была завершена
							'EMERGENCY_STOP'    => 0, //отправка сообщения не была аварийно остановлена
							//'PORTAL' => $msg['PORTAL'],	
							'TYPE'   => strtoupper( $msg['TYPE'] ),		
							'EVENT'  => strtoupper( $msg['EVENT'] ),		
							'VALUE'  => json_encode( $msg['VALUE'] ),		
							//'VALUE'  => $msg_value_converted,			
				 )
			);
			if( $result->isSuccess() )
			{
				return true;
			}
			else
			{
				echo(' ОШИБКА ' . implode(', ', $result->getErrors()) . "\n");
				return false;
			}
			
	}
 
	function GetMessage()//взять сообщение из локальной очереди
	{
			/*
				DATE_COMPLETED		Дата со временем	
				DATE_STARTED		Дата со временем	
				PROCESS_COMPLETED	Да/Нет	
				PROCESS_STARTED		Да/Нет	
				ERROR				Строка	
				RESULT				Строка	
				MESSAGE				Строка	
				MESSAGE_TYPE		Строка	
				EMERGENCY_STOP		Да/Нет
			*/
			
		
			$resData = CESBServerQueueTable::getList(array(
					'select' => array( 'ID', 'DATE_COMPLETED', 'DATE_STARTED', 'PROCESS_COMPLETED', 'PROCESS_STARTED',  'VALUE', 'TYPE', 'EVENT', 'OBJECT' ),
					'filter' => array( 'PROCESS_STARTED' => '0' ),
					'order'  => array( 'ID' => 'ASC' ),
					'limit'  => '1',
			));

			if( $arItem = $resData->Fetch() ) {

						$this->entity_item_id = $arItem['ID'];
						$this->message        = array( 'TYPE' => $arItem['TYPE'], 'EVENT' => $arItem['EVENT'], 'OBJECT' => $arItem['OBJECT'], 'VALUE' => json_decode( $arItem['VALUE'],true ) );
						$this->type        	  = $arItem['TYPE'];
						$this->event          = $arItem['EVENT'];
						$this->value          = $arItem['VALUE'];
						return $arItem;
			}

			return false;
	}
	
	function GetHandlers()
	{
			$resData = '';
			$this->handlers = array();
			
			$resData = CESBServerHandlersTable::getList(array(
					'select' => array( 'ID', 'TYPE', 'EVENT', 'EVENT', 'PORTAL', 'PORTAL_URL' ),
					'filter' => array( 'TYPE' => $this->type, 'EVENT' => $this->event ),
					'order'  => array( 'ID' => 'ASC' ),
					'limit'  => '1000',
			));

			while( $arItem = $resData->Fetch() ) {
					$this->handlers[] = $arItem;
					//return $arItem;
			}
			
			\Bitrix\Main\Diag\Debug::dumpToFile('GetHandlers');
			\Bitrix\Main\Diag\Debug::dumpToFile($this->handlers);
			
			if( count($this->handlers) > 0 ) return $this->handlers;

			return false;
	}
	
	function GetSessionExecutingTime()//сколько секунд длится текущая сессия
	{
			echo ' [session: '.( (float)( $this->GetMicroTime() - $this->time_start ) ).'s]';
			return (float)( $this->GetMicroTime() - $this->time_start );
	}
	
	function IsSessionLive()//Проверка время жизни текущей сессии
	{
		
			return ( $this->GetSessionExecutingTime() <= $this->max_session_executing_time ); //если время выполнения скрипта меньше минуты, продолжаем брать задания из очереди и продолжать выполнение, потому что частоту выполнения в crontab меньше минуты не поставить
	}
	
	function Execute()
	{
		if( $ExecutingMessages = $this->GetExecutingMessage() ) //найти все задачи которые выполняются
		{
				foreach($ExecutingMessages as $ExecutingMessage)
				{
					if( $this->MessageExecutingTooLong( $ExecutingMessage ) ) //если задача выполняется слишком долго
					{
						$this->ExecutingMessageEmergencyStop( $ExecutingMessage ); //тогда остановить её с соответствующим флагом
					}
				}
		}
		
		//берём следующую из очереди
		while($this->IsSessionLive()){

			$result = false;
			$handlers = false;
			$this->response = '';

			if($task = $this->GetMessage()){

                $result = $this->SendMessage();

                if($result !== false){
                    $this->CompleteMessage();
                }
			}

			usleep(10000);
		}
		
	}
	
}


	
	
class CESBServer
{
	var $esb_server_url;
	
	public function __construct()
	{
        $this->esb_server_url = 'https://esb.rosneft.ru/bitrix/modules/esb_server/listener.php'; //"http://user:pass@host:port/path/?query"
		$this->handlers = array();
	}
	
	function push( $msg, $msg_type = 'JSON' )
	{
		$msg_local_queue_id = Queue::AddMessage( $msg, $msg_type );
		return $msg_local_queue_id;
	}
	
	function pushForce( $msg, $msg_type = 'JSON' )//отправка сообщения минуя очередь
	{
		if( $this->msg != '')
		{
			$options = array(
						"disableSslVerification" => true, // true - отключить проверку ssl (с 15.5.9)
			);
			$httpClient = new HttpClient( $options );
			$httpClient->setHeader('Content-Type', 'application/json', true);
			$response = $httpClient->post( $this->esb_server_url, $this->msg );
		}
		
		return $msg_local_queue_id;
	}
	
	function pull()
	{
		
	}
	
	function addEventHandler( $type, $event, $handler )
	{
		    $this->handlers[$type][$event][] = $handler;
	}
	
	function listener($msg_id, $type, $event, $value)
	{
		if ( isset($this->handlers[$type][$event]) && is_array($this->handlers[$type][$event]) && count($this->handlers[$type][$event]) > 0)
		{
			foreach( $this->handlers[$type][$event] as $callback)
			{
				$result = call_user_func_array($callback, $value); //вызываем обработчик					
			}
		}
	}
}	
	
	
	
    class __CESBServer
    {
        const CACHE_DIR = '/bitrix/cache/custom.esb_server/';

        /**
        * Обработчик события OnPageStart
        *
        * 1.Определяет есть ли подходящее правило кэширования. Если нет тогда выполнение метода завершается и продолжается выполение страницы.
        * 2.Если найдено подходящее правило кэширования, тогда проверяет существует ли файл кэша, если существует, то проверяет его возраст. Если файл кэша не просрочен, тогда тогда браузеру отдаётся его содержимое. Выполнение страницы прекращается.
        * 3.Если найдено подходящее правило кэширования, но файла кэша не существует или он просрочен, тогда начинается буферизация и продолжается выполнение страницы
        */
    	function OnPageStart()
    	{
                if( $_SERVER['REQUEST_METHOD'] != 'GET' ) return; //кэширование работает только для GET запросов

    	        $ENCODING = CESBServer::CheckCanGzip();
                $filter_term = array('ACTIVE'=>'Y');
                if( \CUser::IsAuthorized() ) $filter_term['ALLOW_CACHING_FOR_AUTHORIZED_USERS']='Y'; //если авторизован тогда выбирать только те правила, в которых разрешено кэщирование для авторизованных посетителей.
    	        if( $GLOBALS['APPLICATION']->GetCurDir() == '/bitrix/admin/' ) return; //и кэширование не работает для админки


                $terms = CESBServerQueueTable::getList(array('filter'=>$filter_term));
                while( $term = $terms->fetch() )
                {

                        if( strlen(trim( $term['TERM_PAGE'] )) > 0 )//страница
                        {

                                if( $term['TERM_PAGE'] == $GLOBALS['APPLICATION']->GetCurPage() || $term['TERM_PAGE'] == $GLOBALS['APPLICATION']->GetCurPage(true) )//найдено правило
                                {
                                    CheckDirPath($_SERVER['DOCUMENT_ROOT'].self::CACHE_DIR.$term['ID'].'/');
                                    $GLOBALS['ESB_CLIENT']['term'] = $term;

                                    if( $term['USE_URL_PARAMS'] == 'Y' )
                                    {
                                            $GLOBALS['ESB_CLIENT']['hash_file_path'] = $_SERVER["DOCUMENT_ROOT"].self::CACHE_DIR.$term['ID'].'/'.md5( $GLOBALS['APPLICATION']->GetCurPageParam('', array(), true) );

                                            if( $GLOBALS['ESB_CLIENT']['term']['COMPRESSION'] == 'Y' && $ENCODING !== 0 )
                                                $GLOBALS['ESB_CLIENT']['hash_file_path'] .= '.gz';
                                            else
                                                $GLOBALS['ESB_CLIENT']['hash_file_path'] .= '.htm';
                                    }
                                    else
                                    {
                                            $GLOBALS['ESB_CLIENT']['hash_file_path'] = $_SERVER["DOCUMENT_ROOT"].self::CACHE_DIR.$term['ID'].'/'.md5( $GLOBALS['APPLICATION']->GetCurPage(true) );

                                            if( $GLOBALS['ESB_CLIENT']['term']['COMPRESSION'] == 'Y' && $ENCODING !== 0 )
                                                $GLOBALS['ESB_CLIENT']['hash_file_path'] .= '.gz';
                                            else
                                                $GLOBALS['ESB_CLIENT']['hash_file_path'] .= '.htm';
                                    }

                                    break;
                                }
                        }
                        elseif( strlen(trim( $term['TERM_DIR'] )) > 0 )//раздел
                        {

                                if( $term['TERM_DIR'] == $GLOBALS['APPLICATION']->GetCurDir() )//сработало правило
                                {
                                        CheckDirPath($_SERVER['DOCUMENT_ROOT'].self::CACHE_DIR.$term['ID'].'/');
                                        $GLOBALS['ESB_CLIENT']['term'] = $term;

                                        if( $term['USE_URL_PARAMS'] == 'Y' )
                                        {
                                                $GLOBALS['ESB_CLIENT']['hash_file_path'] = $_SERVER["DOCUMENT_ROOT"].self::CACHE_DIR.$term['ID'].'/'.md5( $GLOBALS['APPLICATION']->GetCurPageParam('', array(), true) );

                                                if( $GLOBALS['ESB_CLIENT']['term']['COMPRESSION'] == 'Y' && $ENCODING !== 0 )
                                                    $GLOBALS['ESB_CLIENT']['hash_file_path'] .= '.gz';
                                                else
                                                    $GLOBALS['ESB_CLIENT']['hash_file_path'] .= '.htm';
                                        }
                                        else
                                        {
                                                $GLOBALS['ESB_CLIENT']['hash_file_path'] = $_SERVER["DOCUMENT_ROOT"].self::CACHE_DIR.$term['ID'].'/'.md5( $GLOBALS['APPLICATION']->GetCurPage(true) );

                                                if( $GLOBALS['ESB_CLIENT']['term']['COMPRESSION'] == 'Y' && $ENCODING !== 0 )
                                                    $GLOBALS['ESB_CLIENT']['hash_file_path'] .= '.gz';
                                                else
                                                    $GLOBALS['ESB_CLIENT']['hash_file_path'] .= '.htm';
                                        }

                                        break;
                                }
                        }
                        elseif( strlen(trim( $term['TERM_PHP_EXPRESSION'] )) > 0 )//PHP выражение
                        {

                            if(@eval("return ".$term['TERM_PHP_EXPRESSION'].";"))
                            {
                                    CheckDirPath($_SERVER['DOCUMENT_ROOT'].self::CACHE_DIR.$term['ID'].'/');
                                    $GLOBALS['ESB_CLIENT']['term'] = $term;

                                    if( $term['USE_URL_PARAMS'] == 'Y' )
                                    {
                                            $GLOBALS['ESB_CLIENT']['hash_file_path'] = $_SERVER["DOCUMENT_ROOT"].self::CACHE_DIR.$term['ID'].'/'.md5( $GLOBALS['APPLICATION']->GetCurPageParam('', array(), true) );

                                              if( $GLOBALS['ESB_CLIENT']['term']['COMPRESSION'] == 'Y' )
                                                $GLOBALS['ESB_CLIENT']['hash_file_path'] .= '.gz';
                                            else
                                                $GLOBALS['ESB_CLIENT']['hash_file_path'] .= '.htm';
                                    }
                                    else
                                    {
                                            $GLOBALS['ESB_CLIENT']['hash_file_path'] = $_SERVER["DOCUMENT_ROOT"].self::CACHE_DIR.$term['ID'].'/'.md5( $GLOBALS['APPLICATION']->GetCurPage(true) );

                                            if( $GLOBALS['ESB_CLIENT']['term']['COMPRESSION'] == 'Y' )
                                                $GLOBALS['ESB_CLIENT']['hash_file_path'] .= '.gz';
                                            else
                                                $GLOBALS['ESB_CLIENT']['hash_file_path'] .= '.htm';
                                    }

                                    break;
                            }
                        }
                        elseif( strlen(trim( $term['TERM_REGULAR_EXPRESSION'] )) > 0 )//регулярное выражение
                        {
                                $term['TERM_REGULAR_EXPRESSION'] = strtolower($term['TERM_REGULAR_EXPRESSION']);
                                $url = strtolower($GLOBALS['APPLICATION']->GetCurPageParam('', array(), true));

                                if( preg_match('/'.$term['TERM_REGULAR_EXPRESSION'].'/sim', $url, $result) )//сработало правило
                                {
                                        CheckDirPath($_SERVER['DOCUMENT_ROOT'].self::CACHE_DIR.$term['ID'].'/');
                                        $GLOBALS['ESB_CLIENT']['term'] = $term;

                                        if( $term['USE_URL_PARAMS'] == 'Y' )
                                        {
                                                $GLOBALS['ESB_CLIENT']['hash_file_path'] = $_SERVER["DOCUMENT_ROOT"].self::CACHE_DIR.$term['ID'].'/'.md5( $GLOBALS['APPLICATION']->GetCurPageParam('', array(), true) );

                                                if( $GLOBALS['ESB_CLIENT']['term']['COMPRESSION'] == 'Y' && $ENCODING !== 0 )
                                                    $GLOBALS['ESB_CLIENT']['hash_file_path'] .= '.gz';
                                                else
                                                    $GLOBALS['ESB_CLIENT']['hash_file_path'] .= '.htm';
                                        }
                                        else
                                        {
                                                $GLOBALS['ESB_CLIENT']['hash_file_path'] = $_SERVER["DOCUMENT_ROOT"].self::CACHE_DIR.$term['ID'].'/'.md5( $GLOBALS['APPLICATION']->GetCurPage(true) );

                                                if( $GLOBALS['ESB_CLIENT']['term']['COMPRESSION'] == 'Y' && $ENCODING !== 0 )
                                                    $GLOBALS['ESB_CLIENT']['hash_file_path'] .= '.gz';
                                                else
                                                    $GLOBALS['ESB_CLIENT']['hash_file_path'] .= '.htm';
                                        }

                                        break;
                                }
                        }


                }



                if( is_file($GLOBALS['ESB_CLIENT']['hash_file_path']) )//Файл кэша есть
                {

                        $current_time=time(); // текущее время
                        $time_last_change_file=filemtime( $GLOBALS['ESB_CLIENT']['hash_file_path'] ); // время последнего изменения файла
                        $time_delta=$current_time-$time_last_change_file;  //время жизни файла (в секундах)


                        if( intval($time_delta) <= intval($GLOBALS['ESB_CLIENT']['term']['TIME']) )//кэш ещё актуален
                        {
                            if( $GLOBALS['ESB_CLIENT']['term']['COMPRESSION'] == 'Y' )
                            {

                                $ENCODING = CESBServer::CheckCanGzip();
                        		if($ENCODING !== 0)
                        		{

                                    $Contents = file_get_contents( $GLOBALS['ESB_CLIENT']['hash_file_path'] );

                        			header("Content-Encoding: $ENCODING");
                                    header("X-ESBClient: Y");
                        			print "\x1f\x8b\x08\x00\x00\x00\x00\x00";
                        			print $Contents;
                                    die();
                        		}

                            }
                            else
                            {
                                header("X-ESBClient: Y");
                                echo file_get_contents( $GLOBALS['ESB_CLIENT']['hash_file_path'] );
                                die();
                            }
                        }
                        else
                        {
                            //время жизни кэша истекло
                            //удаляем файл
                            if( !unlink( $GLOBALS['ESB_CLIENT']['hash_file_path'] ) ){
                                   AddMessage2Log('Не удалось удалить истёкший файл кэша.');
                            }

                        }
                }
                else
                {
                        //Файла кэша нету
                }

        		ob_start();
        		ob_start(); // second buffering envelope for PHP URL rewrite, see http://bugs.php.net/bug.php?id=35933
        		ob_implicit_flush(0);  //die();
    	}

        /**
        * Обработчик события OnAfterEpilog
        *
        * Сохраняет буферезированный контент в файл кэша
        */
    	function OnAfterEpilog()
    	{
                if( $_SERVER['REQUEST_METHOD'] != 'GET' ) return; //кэширование работает только для GET запросов
                if( \CUser::IsAuthorized() && $GLOBALS['ESB_CLIENT']['term']['ALLOW_CACHING_FOR_AUTHORIZED_USERS'] != 'Y' ) return; //Если правилу не разрешено кэширование для авторизованных пользователей тогда прекращаем работу
    	        if( $GLOBALS['APPLICATION']->GetCurDir() == '/bitrix/admin/' ) return; //и кэширование не работает для админки


                if(
                    strlen(trim($GLOBALS['ESB_CLIENT']['hash_file_path'])) > 0 && //если найдено подходящее правило
                    !is_file($GLOBALS['ESB_CLIENT']['hash_file_path']) &&         //файл кэша ещё не существует
                    ERROR_404 != 'Y'                                            //на странице не произошла 404 ошибка
                )
                {

                        $ENCODING = CESBServer::CheckCanGzip();


                        ob_end_flush();
                        $Contents = ob_get_contents();
                        ob_end_clean();


                        $file = fopen( $GLOBALS['ESB_CLIENT']['hash_file_path'],"w+");
                        if( $file )
                        {

                                //компрессия
                                if( $GLOBALS['ESB_CLIENT']['term']['COMPRESSION'] == 'Y' && $ENCODING !== 0 )
                                {
                          			$level = 4;

                          			if(!defined("BX_SPACES_DISABLED") || BX_SPACES_DISABLED!==true)
                          				if((strpos($GLOBALS["HTTP_USER_AGENT"], "MSIE 5")>0 || strpos($GLOBALS["HTTP_USER_AGENT"], "MSIE 6.0")>0) && strpos($GLOBALS["HTTP_USER_AGENT"], "Opera")===false)
                          					$Contents = str_repeat(" ", 2048)."\r\n".$Contents;

                          			$Size = function_exists("mb_strlen")? mb_strlen($Contents, 'latin1'): strlen($Contents);
                          			$Crc = crc32($Contents);
                          			$Contents = gzcompress($Contents, $level);
                          			$Contents = function_exists("mb_substr")? mb_substr($Contents, 0, -4, 'latin1'): substr($Contents, 0, -4);

                                      $Contents.=pack('V',$Crc).pack('V',$Size);

                                      fwrite($file, $Contents);
                                      fclose($file);

                          			header("Content-Encoding: $ENCODING");
                                    print "\x1f\x8b\x08\x00\x00\x00\x00\x00";
                          			print $Contents;
                                    die();
                                }
                                else
                                {
                                    fwrite($file, $Contents);
                                    fclose($file);
                                    echo $Contents;
                                }

                        }
                        //else
                        //        AddMessage2Log('Не удалось открыть файл кэша на запись');
                }
                else
                {
                        ob_end_flush();
                        ob_end_flush();
                }
        }

        /**
        * Метод проверяет возможность использовать компрессию
        */
        function CheckCanGzip()
    	{
    		if(!function_exists("gzcompress")) return 0;
    		//if(defined("BX_COMPRESSION_DISABLED") && BX_COMPRESSION_DISABLED===true) return 0;
    		if(headers_sent() || connection_aborted()) return 0;
    		if(ini_get('zlib.output_compression') == 1) return 0;
    		if($GLOBALS["HTTP_ACCEPT_ENCODING"] == '') return 0;
    		if(strpos($_SERVER["HTTP_ACCEPT_ENCODING"],'x-gzip') !== false) return "x-gzip";
    		if(strpos($_SERVER["HTTP_ACCEPT_ENCODING"],'gzip') !== false) return "gzip";
    		return 0;
    	}

        /**
        * Метод возвращает путь относительно DOCUMENT_ROOT к директории в которой хранятся файлы кэша для заданного правила
        */
        function ESBClientPath($term_id)
        {
            return self::CACHE_DIR.$term_id.'/';
        }

        /**
        * Метод возвращает путь от корня к директории в которой хранятся файлы кэша для заданного правила, в случае отсутствия таковой директории - создаёт её
        */
        function MakeCachePath($term_id)
        {
            $path = $_SERVER['DOCUMENT_ROOT'].self::CACHE_DIR.$term_id.'/';
            $path = Bitrix\Main\IO\Path::normalize($path);//CFileMan::NormalizePath()
            return CheckDirPath($path);
        }

        /**
        * Метод удаляет файлы кэша для заданного правила
        */
        function ClearCache($term_id)
    	{
    	    @set_time_limit(0);
            DeleteDirFilesEx(self::CACHE_DIR.$term_id.'/');
            //CheckDirPath($_SERVER['DOCUMENT_ROOT'].self::CACHE_DIR.$term_id.'/');
    	}

        /**
        * Метод удаляет все файлы кэша
        */
        function ClearAllCache()
    	{
    	    @set_time_limit(0);
            DeleteDirFilesEx(self::CACHE_DIR);
            //CheckDirPath(self::CACHE_DIR);
    	}
    }

    /**
    * Управление правилами кэширования
    */
    class CESBServerTerms
    {
        const CACHE_DIR = '/bitrix/cache/custom.esb_server/';

        function GetList()
        {
            return CESBServerQueueTable::GetList();
        }

        function Add($arFields)
        {

        }

        function Delete($term_id)
        {
            DeleteDirFilesEx(self::CACHE_DIR.$term_id.'/');
            return CESBServerQueueTable::delete($term_id);
        }

        function Update($term_id, $arFields)
        {

        }
    }
?>