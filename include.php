<?php
\Bitrix\Main\Loader::registerAutoLoadClasses(
    'nm.bus',
    [
        'NM\\Bus'                                      => 'lib/Bus.php',
        'NM\\Bus\\Server'                              => 'lib/Server.php',
        'NM\\Bus\\Client'                              => 'lib/Client.php',
        'NM\\Bus\\Queue'                               => 'lib/Queue.php',
        'NM\\Bus\\Settings'                            => 'lib/Settings.php',
        'NM\\Bus\\ObjectSender'                        => 'lib/ObjectSender.php',
        'NM\\Bus\\ObjectsTable'                        => 'lib/Entity/ObjectsTable.php',
        'NM\\Bus\\ReceiversTable'                      => 'lib/Entity/ReceiversTable.php',
        'NM\\Bus\\ObjectsHandlersTable'                => 'lib/Entity/ObjectsHandlersTable.php',
        'NM\\Bus\\ChangeElementHandler'                => 'lib/Handler/ChangeElementHandler.php',

        'Custom\\ESB\\Client\\Queue'                   => 'lib/old/esb_client.php',
        'Custom\\ESB\\Client\\QueueIncoming'           => 'lib/old/esb_client.php',
        'Custom\\ESB\\Client\\CESBClient'              => 'lib/old/esb_client.php',
        'Custom\\ESB\\Client\\CESBClientQueueTable'    => 'lib/old/esb_client.php',

        'Custom\\ESB\\Server\\Queue'                   => 'lib/old/esb_server.php',
        'Custom\\ESB\\Server\\CESBServerHandlersTable' => 'lib/old/esb_server.php',
        'Custom\\ESB\\Server\\CESBServer'              => 'lib/old/esb_server.php',
        'Custom\\ESB\\Server\\CESBServerQueueTable'    => 'lib/old/esb_server.php'

        //'\Bitrix\ESBClient\CESBClient'             => 'lib/old/esb_client.php',
        //'\Bitrix\ESBClient\CESBClient'             => 'lib/old/esb_client.php',
    ]
);
