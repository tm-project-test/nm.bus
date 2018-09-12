<?
    namespace Custom\ESB\Client;

    use Bitrix\Main\Application;
    use Bitrix\Main\Entity;
    use Bitrix\Main\Type\DateTime;
	use Bitrix\Main\Web\HttpClient;

    /**
    * Описание таблицы b_esb_client_queue для ORM
    */
    class CESBClientQueueTable extends Entity\DataManager
    {
        public static function getFilePath()
        {
            return __FILE__;
        }

        public static function getTableName()
        {
            return 'b_esb_client_queue';
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
                ),*/

				/*'PORTAL' => array(     
                    'data_type' => 'string',
                ),*/
				'TYPE' => array(     
                    'data_type' => 'string',
                ),
				'EVENT' => array(     
                    'data_type' => 'string',
                ),
                'OBJECT' => array(
                    'data_type' => 'string',
                ),
				'VALUE' => array(     
                    'data_type' => 'string',
                ),
				
                'DIRECTION' => array(     
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
	var $result;
	
	//var $response;
	var $connection_error;
	var $connection_result;
	var $connection_status;
	var $connection_headers;
	
	
	
	var $time_start; //время начала 
	var $task; 		 //код 
	
	var $type; 		 	// 
	var $event; 		 	// 
	var $object;
	var $value; //
	
	var $max_task_executing_time; //максимальное время отведённое для выполнения одной задачи 
	var $max_session_executing_time; //максимальное время отведённое на сессию (за одну сессию может выполняться несколько заданий из очереди) 
	var $esb_server_url;
	
	public function __construct()
	{
        $this->time_start = $this->GetMicroTime();//дата начала 
		$this->operation_log = array();
		$this->result = '';
		$this->response = '';
		$this->connection_error = '';
		$this->connection_result = '';
		$this->connection_status = '';
		$this->connection_headers = '';
		$this->type = '';
		$this->event = '';
		$this->value = '';


        $settings = new \NM\Bus\Settings();
		$this->esb_server_url = trim($settings->getOption('bus_url'), '/ ') . '/local/modules/nm.bus/tools/bus.php';

		$this->max_task_executing_time = 600; //максимальное время отведённое для выполнения одной задачи 600 секунд
		$this->max_session_executing_time = 59; //сессия может длится максимум 59 секунд. Cron запускает скрипт раз в минуту, поэтому ставим ограничение в 59 секунд, чтобы уменьшить вероятность наложения нескольких сессий друг на друга
	}
	
	function GetMicroTime()
	{
		list($usec, $sec) = explode(' ', microtime());
		return ((float)$usec + (float)$sec);
	}
	
	function GetExecutingMessage()//выбрать все выполняемые в данный момент задачи
	{
			$resData = CESBClientQueueTable::getList(array(
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
			$result = CESBClientQueueTable::update(
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
			$result = CESBClientQueueTable::update(
				 $this->entity_item_id,//$arItem['ID'],
				 array(
							//'RESPONSE' => $this->response,
							//'RESPONSE' => $this->result,
							'CONNECTION_ERROR'   => $this->connection_error,    
							'CONNECTION_RESULT'  => $this->connection_result,   
							'CONNECTION_STATUS'  => $this->connection_status,  
							'CONNECTION_HEADERS' => $this->connection_headers,  
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
	}
	
	function CompleteMessage()//завершение работы задачи
	{
	
			$result = CESBClientQueueTable::update(
				 $this->entity_item_id,//$arItem['ID'],
				 array(
							'PROCESS_COMPLETED' => 1,
							'DATE_COMPLETED' => (new DateTime()),
							//'RESPONSE' => print_r($this->response,true),
							//'RESPONSE' => '!!!!!!!!!!!!',
							//'ERROR' => '',
				 )
			);
			
			//$this->SaveLOG();			
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
			
			
			//print_r( $arItem );
			//die();
			$result = CESBClientQueueTable::update(//устанавливаем флаг что попытка отправки сообщения в шина начата
				 $this->entity_item_id,
				 array(
							'PROCESS_STARTED' => 1,
							'DATE_STARTED' => (new DateTime()),
				 )
			);
			if( $result->isSuccess() )
			{
				//$esb_client = new CESBClient();
				//$response = $esb_client->push();  //отправляем сообщение в шину через клиент
				
				if( $this->message != '')
				{
                    $options = array(
								"disableSslVerification" => true, // true - отключить проверку ssl (с 15.5.9)
					);

					$httpClient = new HttpClient( $options );
					$httpClient->setHeader('Content-Type', 'application/json', true);

					$this->response = $httpClient->post( $this->esb_server_url, json_encode( $this->message ) );

					$this->connection_error   = print_r($httpClient->getError(), true);
					$this->connection_result  = print_r($httpClient->getResult(), true);
					$this->connection_status  = print_r($httpClient->getStatus(), true);
					$this->connection_headers = print_r($httpClient->getHeaders()->toArray(), true);
				}				
				
				//echo " OK ";
				/*echo "\n\neval[\n";
				print_r( $this->task );
				echo "\n]eval\n\n";
				
				$arResult = eval( $this->task );*/
				//print_r($arResult);				
				
				//$this->SetLOG($arResult['LOG']);
				//$this->SetResult($arResult['RESULT']);
				
				
				//return $response;
				return true;
			}
			else
			{
				//echo(' ОШИБКА ' . implode(', ', $result->getErrors()) . "\n");
				return false;
			}
	}
	
	function AddMessage($msg, $direction = 'OUTGOING' /*$msg_type = 'JSON'*/) //добавить сообщение /* направление OUTGOING | INCOMING  */
	{
			$result = CESBClientQueueTable::add(
				 array(
							'PROCESS_STARTED'   => 0, //отправка сообщения не была запущена
							'PROCESS_COMPLETED' => 0, //отправка сообщения не была завершена
							'EMERGENCY_STOP'    => 0, //отправка сообщения не была аварийно остановлена
							'TYPE'   => strtoupper( $msg['TYPE'] ),		
							'EVENT'  => $msg['EVENT'],
							'OBJECT'  => $msg['OBJECT'],
							'VALUE'  => json_encode( $msg['VALUE'] ),
							'DIRECTION'	=> $direction
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
			
			$this->result = '';
			$this->response = '';
			$this->type = '';
			$this->event = '';
			$this->value = '';

			$resData = CESBClientQueueTable::getList(array(
					'select' => array( 'ID', 'DATE_COMPLETED', 'DATE_STARTED', 'PROCESS_COMPLETED', 'PROCESS_STARTED', /*'ERROR', 'RESPONSE', 'MESSAGE', 'MESSAGE_TYPE',*/ 'TYPE', 'EVENT', 'OBJECT', 'VALUE' ),
					'filter' => array( 'PROCESS_STARTED' => '0', 'DIRECTION' => 'OUTGOING' ),
					'order'  => array( 'ID' => 'ASC' ),
					'limit'  => '1',
			));

			if( $arItem = $resData->Fetch() ) {

						$this->entity_item_id = $arItem['ID'];
						
						$this->type         = $arItem['TYPE'];
						$this->event        = $arItem['EVENT'];
						$this->object        = $arItem['OBJECT'];
						$this->value        = json_decode( $arItem['VALUE'],true );
						$this->message      = array( 'TYPE' => $this->type, 'EVENT'=> $this->event, 'OBJECT'=> $this->object, 'VALUE'=> $this->value);
						return $arItem;
			}

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
		//\Bitrix\Main\Loader::includeModule("highloadblock");
	
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
		while( $this->IsSessionLive() ) //если время жизни текущей сессии ещё в порядке. Сессея - это один вызов скрипта из крона, за время жизни сессии может быть отработано несколько заданий из очереди
		{
			$result = false;
			echo ($i++)."\t";
			if( $task = $this->GetMessage() )//взять сообщение из очереди
			{
				$result = $this->SendMessage();
				if( $result !== false )//отправить сообщение
				{
					$this->CompleteMessage();   //завершить сообщение
					//return $this->CompleteMessage();   //завершить сообщение
				}
				/*else
				{
					return false;
				}*/
			}
			usleep(10000);
			//else//если задач в очереди нет, тогда выходим (завершаем сессию)
			//{
			//	return false;
			//}
		}
		
	}
	
}


//////////////////////////////////////////////////////////////////////////////////////////////

class QueueIncoming
{
	var $operation_log;
	var $result;
	
	//var $response;
	var $connection_error;
	var $connection_result;
	var $connection_status;
	var $connection_headers;
	
	
	
	var $time_start; //время начала 
	var $task; 		 //код 
	
	var $type; 		 	// 
	var $event; 		 	// 
	var $value; //
	
	var $max_task_executing_time; //максимальное время отведённое для выполнения одной задачи 
	var $max_session_executing_time; //максимальное время отведённое на сессию (за одну сессию может выполняться несколько заданий из очереди) 
	var $esb_server_url;
	
	public function __construct()
	{
        $this->time_start = $this->GetMicroTime();//дата начала 
		$this->operation_log = array();
		$this->result = '';
		$this->response = '';
		//$this->connection_error = '';
		//$this->connection_result = '';
		//$this->connection_status = '';
		//$this->connection_headers = '';
		$this->type = '';
		$this->event = '';
		$this->value = '';
		
		
		//$this->esb_server_url = 'http://192.168.0.103/bitrix/tools/custom.esb_server/queue.php';
		$this->max_task_executing_time = 600; //максимальное время отведённое для выполнения одной задачи 600 секунд
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
			$resData = CESBClientQueueTable::GetList(array(
					'select' => array( 'ID', 'DATE_COMPLETED', 'DATE_STARTED', 'PROCESS_COMPLETED', 'PROCESS_STARTED', 'EMERGENCY_STOP', /*'MESSAGE', 'MESSAGE_TYPE'*/),
					'filter' => array( 'PROCESS_STARTED' => '1', 'PROCESS_COMPLETED' => '0', 'DIRECTION'  => 'INCOMING' ),
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
			$result = CESBClientQueueTable::update(
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
			$result = CESBClientQueueTable::update(
				 $this->entity_item_id,//$arItem['ID'],
				 array(
							//'RESPONSE' => $this->response,
							//'RESPONSE' => $this->result,
							'CONNECTION_ERROR'   => $this->connection_error,    
							'CONNECTION_RESULT'  => $this->connection_result,   
							'CONNECTION_STATUS'  => $this->connection_status,  
							'CONNECTION_HEADERS' => $this->connection_headers,  
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
	}
	
	function CompleteMessage()//завершение работы задачи
	{
	
			$result = CESBClientQueueTable::update(
				 $this->entity_item_id,//$arItem['ID'],
				 array(
							'PROCESS_COMPLETED' => 1,
							'DATE_COMPLETED' => (new DateTime()),
							//'RESPONSE' => print_r($this->response,true),
							//'RESPONSE' => '!!!!!!!!!!!!',
							//'ERROR' => '',
				 )
			);
			
			//$this->SaveLOG();			
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
	
	function ExecuteMessage()//выполняем сообщение
	{
			
			
			//print_r( $arItem );
			//die();
			$result = CESBClientQueueTable::update(//устанавливаем флаг что попытка отправки сообщения в шина начата
				 $this->entity_item_id,
				 array(
							'PROCESS_STARTED' => 1,
							'DATE_STARTED' => (new DateTime()),
				 )
			);
			if( $result->isSuccess() )
			{
				//$esb_client = new CESBClient();
				//$response = $esb_client->push();  //отправляем сообщение в шину через клиент
				
				if( $this->message != '')
				{
				    if(\CModule::IncludeModule('nm.bus')){
                        $queue = new \NM\Bus\Queue();
                        $queue->processMessage($this->message['VALUE']);
                    }

                    /*
					$event = new \Bitrix\Main\Event("custom.esb_client", $this->message["TYPE"].".".$this->message["EVENT"], $this->message["VALUE"]);
					   $event->send();
					   if ($event->getResults()){
						  foreach($event->getResults() as $evenResult){
							 if($evenResult->getResultType() == \Bitrix\Main\EventResult::SUCCESS){
								$this->connection_result = 'Y';
							}
							else{$this->connection_result = 'N';}
					   }
					}
					*/

					/*$this->connection_error   
					$this->connection_result  
					$this->connection_status  
					$this->connection_headers*/ 
					
				}				
				
				//echo " OK ";
				/*echo "\n\neval[\n";
				print_r( $this->task );
				echo "\n]eval\n\n";
				
				$arResult = eval( $this->task );*/
				//print_r($arResult);				
				
				//$this->SetLOG($arResult['LOG']);
				//$this->SetResult($arResult['RESULT']);
				
				
				//return $response;
				return true;
			}
			else
			{
				//echo(' ОШИБКА ' . implode(', ', $result->getErrors()) . "\n");
				return false;
			}
	}
	
	function AddMessage($msg, $direction = 'INCOMING' /*$msg_type = 'JSON'*/) //добавить сообщение /* направление OUTGOING | INCOMING  */
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
			echo '</pre>';*/
			
			$result = CESBClientQueueTable::add(
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
							'DIRECTION'	=> $direction,
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
			
			$this->result = '';
			$this->response = '';
			$this->type = '';
			$this->event = '';
			$this->value = '';
			$this->message = '';

			$resData = '';
			$resData = CESBClientQueueTable::getList(array(
					'select' => array( 'ID', 'DATE_COMPLETED', 'DATE_STARTED', 'PROCESS_COMPLETED', 'PROCESS_STARTED', /*'ERROR', 'RESPONSE', 'MESSAGE', 'MESSAGE_TYPE',*/ 'TYPE', 'EVENT', 'VALUE' ),
					'filter' => array( 'PROCESS_STARTED' => '0', 'DIRECTION'  => 'INCOMING'),
					'order'  => array( 'ID' => 'ASC' ),
					'limit'  => '1',
			));

			if( $arItem = $resData->Fetch() ) {

						$this->entity_item_id = $arItem['ID'];
						
						$this->type        = $arItem['TYPE'];
						$this->event        = $arItem['EVENT'];
						$this->value        = json_decode( $arItem['VALUE'],true );
						$this->message      = array( 'TYPE' => $this->type, 'EVENT'=> $this->event, 'VALUE'=> $this->value);
						return $arItem;
			}

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
		//\Bitrix\Main\Loader::includeModule("highloadblock");
	
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
		while( $this->IsSessionLive() ) //если время жизни текущей сессии ещё в порядке. Сессея - это один вызов скрипта из крона, за время жизни сессии может быть отработано несколько заданий из очереди
		{
			$result = false;
			echo ($i++)."\t";
			if( $task = $this->GetMessage() )//взять сообщение из очереди
			{
				$result = $this->ExecuteMessage();  print_r('955') ;print_r($result);
				if( $result !== false )//отправить сообщение
				{
					$this->CompleteMessage();   //завершить сообщение
					//return $this->CompleteMessage();   //завершить сообщение
				}
				/*else
				{
					return false;
				}*/
			}
			usleep(10000);
			//else//если задач в очереди нет, тогда выходим (завершаем сессию)
			//{
			//	return false;
			//}
		}
		
	}
	
}













/*class QueueIncoming	extends Queue
{
	GetMessage()
}*/

	
class CESBClient
{
	var $esb_server_url;
	
	public function __construct()
	{
        $settings = new \NM\Bus\Settings();
        $this->esb_server_url = trim($settings->getOption('bus_url'), '/ ') . '/local/modules/nm.bus/tools/bus.php';
		$this->handlers = array();
	}
	
	function push( $msg )
	{
		$msg_local_queue_id = Queue::AddMessage( $msg );
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
	
	
	
    class __CESBClient
    {
        const CACHE_DIR = '/bitrix/cache/custom.esb_client/';

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

    	        $ENCODING = CESBClient::CheckCanGzip();
                $filter_term = array('ACTIVE'=>'Y');
                if( \CUser::IsAuthorized() ) $filter_term['ALLOW_CACHING_FOR_AUTHORIZED_USERS']='Y'; //если авторизован тогда выбирать только те правила, в которых разрешено кэщирование для авторизованных посетителей.
    	        if( $GLOBALS['APPLICATION']->GetCurDir() == '/bitrix/admin/' ) return; //и кэширование не работает для админки


                $terms = CESBClientQueueTable::getList(array('filter'=>$filter_term));
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

                                $ENCODING = CESBClient::CheckCanGzip();
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

                        $ENCODING = CESBClient::CheckCanGzip();


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
    class CESBClientTerms
    {
        const CACHE_DIR = '/bitrix/cache/custom.esb_client/';

        function GetList()
        {
            return CESBClientQueueTable::GetList();
        }

        function Add($arFields)
        {

        }

        function Delete($term_id)
        {
            DeleteDirFilesEx(self::CACHE_DIR.$term_id.'/');
            return CESBClientQueueTable::delete($term_id);
        }

        function Update($term_id, $arFields)
        {

        }
    }
?>