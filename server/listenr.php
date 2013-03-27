<?php

require_once('./../config.php');
require_once(BASE_PATH . '/server/user.php');
require_once(BASE_PATH . '/server/usersList.php');
require_once(BASE_PATH . '/server/map.php');
require_once(BASE_PATH . '/server/response.php');
require_once(BASE_PATH . '/server/action.php');
require_once(BASE_PATH . '/server/interaction.php');
require_once(BASE_PATH . '/server/battle.php');

class Listener {

    protected $maxBufferSize;
    protected $master;
    protected $sockets = array();
    private $config;
    private $messageForAll;

    public function __construct($addr, $port, $bufferLength = 2048) {
        UsersList::getInstance();
        Map::getInstance();
        Battle::getInstance();
        $this->config = Config::getConfig();
        $this->maxBufferSize = $bufferLength;
        $this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("Failed: socket_create()");
        // Коннект к websocketServer (меня там знают как listener)
        socket_connect($this->master, $this->config['server']['addr'], $this->config['server']['port']);
        echo("Server LISTENER started\nListening on: $addr:$port\nMaster socket: " . $this->master);


        $read = array($this->master);
        $write  = NULL;
        $except = NULL;
        while(1) {
            @socket_select($read, $write, $except, 0, 1);
            $numBytes = @socket_recv($this->master, $buffer, 24000, MSG_DONTWAIT); // MSG_DONTWAIT - что бы не блокировал

            $this->periodicManipulation();

            // Смотрим, есть ли сообщения для кого-нибудь в общем пуле.
            if ($this->messageForAll) {
                echo "\nmessageForAll>>>>>>>>>\n";
                var_dump($this->messageForAll);
                echo "\nmessageForAll<<<<<<<<<\n";
                socket_write($this->master, $this->messageForAll);
                $this->messageForAll = null;
            }

            if ($numBytes > 0) {
                // Смотрим, что там нам положил в сокет websocketServer
                $responseToWebsocket = $this->generateResponse(trim($buffer));
                // Пишем в websocketServer (он там дальше проксирует на клиента)
                echo "\noneResponse>>>>>>>>>>>\n";
                var_dump($responseToWebsocket);
                echo "\noneResponse<<<<<<<<<<<\n";
                socket_write($this->master, $responseToWebsocket);
            }
            else{
                usleep(250000);
            }
}

        socket_close($this->master);
    }


    private function generateResponse($requestFromWebsocket){
        $action = new Action($requestFromWebsocket);
        $action->execute();
        $this->messageForAll = $action->getMessageMass();
        return $action->getMessageOne();
    }

    private function periodicManipulation(){
        $battle = Battle::getInstance();
        $battle->execute();
        $this->personalMessages = $battle->getMessagesPersonal();
    }

}

new Listener('0.0.0.0', '8001');

