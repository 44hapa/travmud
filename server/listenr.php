<?php

require_once('./../config.php');
require_once(BASE_PATH . '/server/user.php');
require_once(BASE_PATH . '/server/usersList.php');
require_once(BASE_PATH . '/server/map.php');
require_once(BASE_PATH . '/server/response.php');

class Listener {

    protected $maxBufferSize;
    protected $master;
    protected $sockets = array();
    private $config;
    private $messageForAll;

    /**
     *
     * @var UsersList
     */
    private $usersList;

    /**
     *
     * @var Map
     */
    private $map;

    public function __construct($addr, $port, $bufferLength = 2048) {

        $this->usersList = new UsersList();
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
        list($userId, $message) = explode($this->config['startBuferDelimiter'], $requestFromWebsocket);
        $message = trim($message);
        // Если пользователь еще не авторизован
        if ($messagesJSON = $this->generateResponseIfUserNotConnect($userId, $message)) {
            return $userId . $this->config['startBuferDelimiter'] . $messagesJSON;
        }
        $user = $this->usersList->getUserByWsId($userId);
        if ('кто' == $message) {
            $request = '';
            foreach ($this->usersList->getUsersList() as $user) {
                $request .= "Пользователь ".$user->name . "[$user->wsId]<br>";
            }
            $request .= "<br><hr>";

            $response = new Response();
            $response->request = $message;
            $response->message = "Сейчас в травмаде:<br>$request";
            return $userId . $this->config['startBuferDelimiter'] . $response->toString();
        }
        if ('map' == $message) {
            $response = new Response();
            $response->request = $message;
            $response->message = "Вот те карта";
            $response->partMap = $this->getMap();
            return $userId . $this->config['startBuferDelimiter'] . $response->toString();
        }
        if ('mob' == $message) {
            $response = new Response();
            $response->request = $message;
            $response->message =  "Вот те монстер";
//            $response->mobs = $this->getMob();
            $response->mobName = 'mob1';
            $response->mobActionType = 'create';
            $response->mobActionValue = $this->getMob('mob1');
            return $userId . $this->config['startBuferDelimiter'] . $response->toString();
        }

        // Движение пользователя
        $response = new Response();
        $response->request = $message;
        $response->actionType = 'move';
        $response->actionValue = $message;
        $response->message = "Вы двигаетесь на $message";

        $this->userMove($user, $message);
        // Оповестим всех, что мы двигаемся.
        $responseAll = new Response();
        $responseAll->userName = $user->name;
        $responseAll->userActionType = 'move';
        $responseAll->userActionValue = $message;
        $responseAll->message = 'Пользователь ' . $user->name . ' двинул на ' . $message;

        $this->setMessageForAllExcludeAuthor($user, $responseAll->toString());

        return $userId . $this->config['startBuferDelimiter'] . $response->toString();
    }


    private function generateResponseIfUserNotConnect($wsId, $message){
        $user = $this->usersList->getUserByWsId($wsId);
        if (!$user) {
            $user = new TravmadUser($wsId);
            $this->usersList->addUser($user);

            $response = new Response();
            $response->request = $message;
            $response->message = 'Введите имя';
            return $response->toString();
        }
        if (!$user->name) {
            // Присвоим имя новому пользователю
            $user->name = $message;
            $user->positionX = 4;
            $user->positionY = 4;
            $response = new Response();
            $response->request = $message;
            $response->message = "Теперь ваше имя $message";

            // Зададим нашу позицию и позицию остальных чаров.
            $response->actionType = 'setPosition';
            $response->actionValue = $this->getAllPosition($user);

            // Оповестим всех, что появился новый.
            $responseAll = new Response();
            $responseAll->userName = $user->name;
            $responseAll->userActionType = 'connectChar';
            $responseAll->userActionValue = array($user->name => $this->getChar($user));
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
        if (count($this->usersList->getUsersList()) < 2) {
            return;
        }

        $usersAsWsId = $this->usersList->getUsersAsWsId();
        unset($usersAsWsId[$userAuthor->wsId]);

        $usersKeys = array_keys($usersAsWsId);
        $usersKeysString = implode($this->config['userDelimiter'], $usersKeys);
        $this->messageForAll = $usersKeysString . $this->config['startBuferDelimiter'] . $messageForAll;
    }

    /**
     *
     * @param TravmadUser $user
     * @param string $direction
     */
    public function userMove($user, $direction){
        switch ($direction) {
            case 'север':
                $user->positionY -= 1;
                break;
            case 'юг':
                $user->positionY += 1;
                break;
            case 'запад':
                $user->positionX -= 1;
                break;
            case 'восток':
                $user->positionX += 1;
                break;

            default:
                break;
        }
        return;
    }


    private function getAllPosition($user){
        $canvasPositionX = $user->positionX * $this->config['stepSize'];
        $canvasPositionY = $user->positionY * $this->config['stepSize'];

        $positions['myPosition'] = array('positionX' => $canvasPositionX, 'positionY' => $canvasPositionY);
        $positions['charsPosition'] = $this->getAllCharsPositionExcludeAuthor($user);
        return $positions;
    }

    /**
     *
     * @param TravmadUser $userAuthor
     * @return array
     */
    private function getAllCharsPositionExcludeAuthor($userAuthor){
        if (count($this->usersList->getUsersList()) < 2) {
            return null;
        }

        $userListAsName = $this->usersList->getUsersAsName();
        unset($userListAsName[$userAuthor->name]);

        foreach ($userListAsName as $user) {
            $chars[$user->name] = $this->getChar($user);
        }

        return $chars;
    }

    /**
     *
     * @param TravmadUser $user
     * @return string
     */
    private function getChar($user){
        $canvasPositionX = $user->positionX * $this->config['stepSize'];
        $canvasPositionY = $user->positionY * $this->config['stepSize'];
        $data = '
[
    {
        "name": "'.$user->name.'",
        "x": '.$canvasPositionX.',
        "y": '.$canvasPositionY.'
    },
    [
        {

            "character_hue": "067-Goblin01.png",
            "direction": "bottom",
            "type": "fixed",
            "trigger": "event_touch",
            "speed": 1,
            "frequence": 0
        }

    ]
]

            ';
        $data = json_decode($data, true);
        return json_encode($data);
    }



    private function getMob($name){
        $data = '
[
    {
        "name": "'.$name.'",
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

