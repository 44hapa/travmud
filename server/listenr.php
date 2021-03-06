<?php

require_once('./../config.php');
require_once(BASE_PATH . '/server/creatureAbstract.php');
require_once(BASE_PATH . '/server/creatureListAbstract.php');
require_once(BASE_PATH . '/server/user.php');
require_once(BASE_PATH . '/server/usersList.php');
require_once(BASE_PATH . '/server/mob.php');
require_once(BASE_PATH . '/server/mobList.php');
require_once(BASE_PATH . '/server/map.php');
require_once(BASE_PATH . '/server/response.php');
require_once(BASE_PATH . '/server/action.php');
require_once(BASE_PATH . '/server/interaction.php');
require_once(BASE_PATH . '/server/battle.php');
require_once(BASE_PATH . '/server/mobslife.php');
require_once(BASE_PATH . '/server/state.php');
require_once(BASE_PATH . '/server/multicommand.php');

class Listener
{

    protected $maxBufferSize;
    protected $master;
    protected $sockets = array();
    private $config;
    static public $mainCounter = 0;

    /**
     * Сообщение сервера игры (уже подготовленное для сокета)
     * 
     * @var string
     */
    private $serverMessages;

    public function __construct($addr, $port, $bufferLength = 2048)
    {
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
        $write = NULL;
        $except = NULL;
        while (1) {
            self::$mainCounter++;
            @socket_select($read, $write, $except, 0, 1);
            $numBytes = @socket_recv($this->master, $buffer, 24000, MSG_DONTWAIT); // MSG_DONTWAIT - что бы не блокировал
            // Смотрим, есть ли сообщения для кого-нибудь в общем пуле.
            if ($this->serverMessages) {
                echo "\nserverMessages>>>>>>>>>\n";
                var_dump($this->serverMessages);
                echo "\nserverMessages<<<<<<<<<\n";
                socket_write($this->master, $this->serverMessages);
                $this->serverMessages = null;
            }

            $this->periodicManipulation();

            if ($numBytes > 0) {
                // Обработаем данные, которые пришли  в сокет websocketServer
                $this->beginProcess(trim($buffer));
            } else {
                usleep(250000);
            }
        }

        socket_close($this->master);
    }

    private function beginProcess($requestFromWebsocket)
    {
        $action = new Action($requestFromWebsocket);
        $action->execute();
        $this->serverMessages .= $action->getResponseMessage();
    }

    private function periodicManipulation()
    {
        $battle = Battle::getInstance();
        $battle->execute();
        $this->serverMessages .= $battle->getResponseMessage();

        $mobslife = Mobslife::getInstance();
        $mobslife->execute();
        $this->serverMessages .= $mobslife->getResponseMessage();

        $state = State::getInstance();
        $state->execute();
        $this->serverMessages .= $state->getResponseMessage();

        $multicommand = Multicommand::getInstance();
        $multicommand->execute();
        $this->serverMessages .= $multicommand->getResponseMessage();
    }

}

new Listener('0.0.0.0', '8001');

