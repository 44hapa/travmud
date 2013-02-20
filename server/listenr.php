<?php

require_once('./../config.php');
require_once(BASE_PATH . '/server/user.php');
require_once(BASE_PATH . '/server/map.php');

class Listener {

    protected $maxBufferSize;
    protected $master;
    protected $sockets = array();
    private $config;
    private $users = array();

    /**
     *
     * @var Map
     */
    private $map;

    private $templateUserResponse = array(
        'request' => '',
        'response' => array(
            'actionType' => null,
            'actionValue' => null,
            'message' => 'Введите ваше имя',
        ),
        'views' => array(
            'mobs' => array(
            ),
            'users' => array(
            ),
            'partMap' => null
        ),
    );


    public function __construct($addr, $port, $bufferLength = 2048) {
        $this->map = Map::getInstance();
        $this->config = Config::getConfig();
        $this->maxBufferSize = $bufferLength;
        $this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("Failed: socket_create()");
        // Коннект к websocketServer (меня там знают как listener)
        socket_connect($this->master, $this->config['server']['addr'], $this->config['server']['port']);
        echo("Server LISTENER started\nListening on: $addr:$port\nMaster socket: " . $this->master);

        $i = 0;
        while ($i < 30) {
            sleep(1);
            // Читаем, что там нам положил в сокет websocketServer
            $requestFromWebsocket = trim(socket_read($this->master, $this->maxBufferSize));

            // Пишем в websocketServer (он там дальше проксирует на клиента)
            $responseToWebsocket = $this->generateResponse($requestFromWebsocket);
            socket_write($this->master, $responseToWebsocket);
            var_dump($responseToWebsocket);
            $i++;
        }
        socket_close($this->master);
    }


    private function generateResponse($requestFromWebsocket){
        list($userId, $message) = explode('__', $requestFromWebsocket);
        $message = trim($message);
        // Если пользователь еще не авторизован
        if ($messagesJSON = $this->generateResponseIfUserNotConnect($userId, $message)) {
            return $userId . '__' . json_encode($messagesJSON);
        }
        if ('кто' == $message) {
            $request = '';
            foreach ($this->users as $key => $user) {
                $request .= "Пользователь ".$user->name . "[$user->wsId]<br>";
            }
            $request .= "<br><hr>";

            $response = $this->templateUserResponse;
            $response['request'] = $message;
            $response['response'] = array(
                'message' => "Сейчас в травмаде:<br>$request",
            );
            $messagesJSON = json_encode($response);
            return $userId . '__' . $messagesJSON;
        }
        if ('map' == $message) {
            $response = $this->templateUserResponse;
            $response['request'] = $message;
            $response['response'] = array(
                'message' => "Вот те карта",
            );
            $response['views']['partMap'] = $this->getMap();
            $messagesJSON = json_encode($response);
            return $userId . '__' . $messagesJSON;
        }

        $response = $this->templateUserResponse;
        $response['request'] = $message;
        $response['response'] = array(
            'actionType' => 'move',
            'actionValue' => $message,
            'message' => "Вы двигаетесь на $message",
        );
        $messagesJSON = json_encode($response);

        return $userId . '__' . $messagesJSON;
    }


    private function generateResponseIfUserNotConnect($userId, $message){
        $user = $this->getUserByWsId($userId);
        if (!$user) {
            $user = new TravmadUser($userId);
            $this->users[] = $user;

            $response = $this->templateUserResponse;
            $response['request'] = $message;
            $response['response'] = array(
                'message' => 'Введите имя',
            );
            return $response;
        }
        if (!$user->name) {
            $user->name = $message;
            $response = $this->templateUserResponse;
            $response['request'] = $message;
            $response['response'] = array(
                'message' => "Теперь ваше имя $message",
            );
            return $response;
        }
        return false;
    }


    private function getUserByWsId($wsId){
        if (empty($this->users)) {
            return false;
        }
        foreach ($this->users as $key => $user) {
            if ($wsId == $user->wsId) {
                return $user;
            }
        }
        return false;
    }


    /**
     * Временные данные (да, да.. я засрал репозиторий, но по другому никак.)
     * @return type
     */
    private function getMap(){
        $data = '{
            "map": [
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]],
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]],
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]],
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]],
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]],
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]],
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]],
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]],
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]],
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]],
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]],
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]],
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]],
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]],
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]],
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]],
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]],
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]],
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]],
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]],
                [[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null],[1505,null,null]]
            ],
            "propreties": {
                "1505": [0, 0],
                "150": [0, 15]
                }
            }';

        // Да да.. вот так я удаляю табы и перевод строк.. и пробелы.
        $data = json_decode($data, true);
        return json_encode($data);
    }

}

new Listener('0.0.0.0', '8001');

