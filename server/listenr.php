<?php

require_once('./../config.php');
require_once(BASE_PATH . '/server/user.php');
require_once(BASE_PATH . '/server/map.php');
require_once(BASE_PATH . '/server/response.php');

class Listener {

    protected $maxBufferSize;
    protected $master;
    protected $sockets = array();
    private $config;
    private $users = array();
    private $messageForAll;

    /**
     *
     * @var Map
     */
    private $map;

    public function __construct($addr, $port, $bufferLength = 2048) {
        $this->map = Map::getInstance();
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

            // Смотрим, есть ли сообщения для кого-нибудь в общем пуле.
            if ($this->messageForAll) {
                socket_write($this->master, $this->messageForAll);
                $this->messageForAll = null;
            }

            if ($numBytes > 0) {
                // Смотрим, что там нам положил в сокет websocketServer
                $responseToWebsocket = $this->generateResponse(trim($buffer));
                // Пишем в websocketServer (он там дальше проксирует на клиента)
                socket_write($this->master, $responseToWebsocket);
            }
            else{
                usleep(250000);
            }
}


//        $i = 0;
//        while ($i < 30) {
//            sleep(1);
//            // Читаем, что там нам положил в сокет websocketServer
//            $requestFromWebsocket = trim(socket_read($this->master, $this->maxBufferSize));
//
//            // Пишем в websocketServer (он там дальше проксирует на клиента)
//            $responseToWebsocket = $this->generateResponse($requestFromWebsocket);
//            socket_write($this->master, $responseToWebsocket);
//            var_dump($responseToWebsocket);
//            $i++;
//        }
        socket_close($this->master);
    }


    private function generateResponse($requestFromWebsocket){
        list($userId, $message) = explode('__', $requestFromWebsocket);
        $message = trim($message);
        // Если пользователь еще не авторизован
        if ($messagesJSON = $this->generateResponseIfUserNotConnect($userId, $message)) {
            return $userId . '__' . $messagesJSON;
        }
        $user = $this->getUserByWsId($userId);
        if ('кто' == $message) {
            $request = '';
            foreach ($this->users as $user) {
                $request .= "Пользователь ".$user->name . "[$user->wsId]<br>";
            }
            $request .= "<br><hr>";

            $response = new Response();
            $response->request = $message;
            $response->message = "Сейчас в травмаде:<br>$request";
            return $userId . '__' . $response->toString();
        }
        if ('map' == $message) {
            $response = new Response();
            $response->request = $message;
            $response->message = "Вот те карта";
            $response->partMap = $this->getMap();
            return $userId . '__' . $response->toString();
        }
        if ('mob' == $message) {
            $response = new Response();
            $response->request = $message;
            $response->message =  "Вот те монстер";
//            $response->mobs = $this->getMob();
            $response->mobs = $this->getMob2();
            return $userId . '__' . $response->toString();
        }

        // Движение пользователя
        $response = new Response();
        $response->request = $message;
        $response->actionType = 'move';
        $response->actionValue = $message;
        $response->message = "Вы двигаетесь на $message";

        $this->userMove($user, $message);
        // Оповестим всех, что мы двигаемся.
        //TODO

        return $userId . '__' . $response->toString();
    }


    private function generateResponseIfUserNotConnect($userId, $message){
        $user = $this->getUserByWsId($userId);
        if (!$user) {
            $user = new TravmadUser($userId);
            $this->users[] = $user;

            $response = new Response();
            $response->request = $message;
            $response->message = 'Введите имя';
            return $response->toString();
        }
        if (!$user->name) {
            // Присвоим имя новому пользователю
            $user->name = $message;
            $user->positionX = 3;
            $user->positionY = 3;
            $response = new Response();
            $response->request = $message;
            $response->message = "Теперь ваше имя $message";

            // Зададим нашу позицию.
            $response->actionType = 'setPosition';
            $response->actionValue = array('positionX' => $user->positionX, 'positionY' => $user->positionY);
            // Передадим новому пользователю координаты остальных
            $response->users = $this->getAllCharsExcludeAuthor($user);

            // Оповестим всех, что появился новый.
            $responseAll = new Response();
            $responseAll->users = array($user->name => $this->getChar($user));
            $responseAll->message = 'Появился пользователь ' . $user->name;

            $this->setMessageForAllExcludeAuthor($user, $responseAll->toString());

            return $response->toString();
        }
        return false;
    }


    /**
     *
     * @param TravmadUser $userAuthor
     * @param string $messageForAll
     * @return string
     */
    private function setMessageForAllExcludeAuthor($userAuthor, $messageForAll){
        // Если только один пользователи, и тот исключен - возвращаемся.
        if (count($this->users) < 2) {
            return;
        }
        $usersKeys = array();
        foreach ($this->users as $user) {
            if ($userAuthor != $user) {
                $usersKeys[] = $user->wsId;
            }
        }
        $usersKeysString = implode('_', $usersKeys);
        $this->messageForAll = $usersKeysString . '__' . $messageForAll;
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
     *
     * @param TravmadUser $user
     * @param string $direction
     */
    public function userMove($user, $direction){
        switch ($direction) {
            case 'север':
                $user->positionY -= $this->config['stepSize'];
                break;
            case 'юг':
                $user->positionY += $this->config['stepSize'];
                break;
            case 'запад':
                $user->positionX -= $this->config['stepSize'];
                break;
            case 'восток':
                $user->positionX += $this->config['stepSize'];
                break;

            default:
                break;
        }
        return;
    }


    /**
     *
     * @param TravmadUser $userAuthor
     * @return array
     */
    private function getAllCharsExcludeAuthor($userAuthor){
        if (count($this->users) < 2) {
            return null;
        }
        $chars = array();
        foreach ($this->users as $user) {
            if ($user != $userAuthor) {
                $chars[$user->name] = $this->getChar($user);
            }
        }
        return $chars;
    }

    /**
     *
     * @param TravmadUser $user
     * @return string
     */
    private function getChar($user){
        $data = '
[
    {
        "name": "'.$user->name.'",
        "x": '.$user->positionX.',
        "y": '.$user->positionY.'
    },
    [
        {

            "character_hue": "067-Goblin01.png",
            "direction": "bottom",
            "type": "fixed",
            "trigger": "event_touch",
            "speed": 3,
            "frequence": 0
        }

    ]
]

            ';
        $data = json_decode($data, true);
        return json_encode($data);
    }



    private function getMob(){
        $data = '
[
    {
        "name": "monster1",
        "x": 19,
        "y": 21
    },
    [
        {

            "character_hue": "067-Goblin01.png",
            "direction": "bottom",
            "type": "random",
            "trigger": "event_touch",
            "speed": 3,
            "frequence": 0,
            "action_battle": {
                "area": 2,
                "hp_max": 300,
                "animation_death": "Darkness 1",
                "actions": ["attack_ennemy"],
                "ennemyDead": [{
                        "name": "coin",
                        "probability": 100,
                        "call": "drop_coin"
                    }],
                "detection": "_default",
                "nodetection": "_default",
                "attack": "_default",
                "affected": "_default",
                "offensive": "_default",
                "passive": "_default"
            }
        }

    ]
]

';
        $data = json_decode($data, true);
        return json_encode($data);
    }

    private function getMob2(){
        $data = '
[
    {
        "name": "monster1",
        "x": 19,
        "y": 21
    },
    [
        {

            "character_hue": "067-Goblin01.png",
            "direction": "bottom",
            "type": "fixed",
            "trigger": "event_touch",
            "speed": 3,
            "frequence": 0
        }

    ]
]

';
        $data = json_decode($data, true);
        return json_encode($data);
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

