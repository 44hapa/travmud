<?php
require_once('./../config.php');
require_once(BASE_PATH . '/server/map.php');

class Action{

    private $config;

    /**
     *
     * @var UsersList
     */
    private $usersList;

    /**
     *
     * @var TravmadUser
     */
    private $userAuthor;


    /**
     *
     * @var Map
     */
    private $map;

    private $responseMessage;

    private $userAuthorWsId;
    private $requestWsMessage;

    /**
     *
     * @param string $requestFromWebsocket
     */
    public function __construct($requestFromWebsocket){
        $this->config = Config::getConfig();
        $this->usersList = UsersList::getInstance();
        $this->map = Map::getInstance();
        list($this->userAuthorWsId, $requestWsMessage) = explode($this->config['startBuferDelimiter'], $requestFromWebsocket);
        $this->requestWsMessage = trim($requestWsMessage);
        $this->userAuthor = $this->usersList->getUserByWsId($this->userAuthorWsId);
    }

    public function execute(){
        // Если пользователь только приконнектился
        if (!$this->userAuthor) {
            $this->connectNewUser();
            return;
        }
        // Если пользователь не авторизован
        if (!$this->userAuthor->name) {
            $this->authorizeUser();
            return;
        }

        list($requestParam1,$requestParam2) = array_pad(explode(" ", $this->requestWsMessage,2), 2, null);

        if ('кто' == $requestParam1) {
            $this->showUsers();
            return;
        }
        if ('map' == $requestParam1) {
            $this->showMap();
            return;
        }
        if ('mob' == $requestParam1) {
            $this->showMob();
            return;
        }
        if ('убить' == $requestParam1) {
            $this->kill($requestParam2);
            return;
        }

        $this->moveUser();
        return;
    }

    public function getResponseMessage(){
        return $this->responseMessage;
    }

    private function addResponseMessage($responseMessage){
        $this->responseMessage .= $responseMessage;
    }

    private function kill($victimName){
        $response = new Response($this->userAuthor);
        $response->request = $this->requestWsMessage;

        if (empty($victimName)) {
            $response->message =  "Убить кого?!";
            $this->addResponseMessage($response->toString());
            return;
        }

        if (!$victim = $this->usersList->getUserByName($victimName)) {
            $response->message =  "Нет такого чара";
            $this->addResponseMessage($response->toString());
            return;
        }

        $interaction = new Interaction($this->userAuthor);

        if ($interaction->tryStryke($victim)) {
            $response->message =  "Ты отоварил $victimName";
            $response->actionType = "stryke";
            $response->actionValue = $victimName;
            $this->addResponseMessage($response->toString());

            $responseVictim = new Response($victim);
            $responseVictim->userName = $this->userAuthor->name;
            $responseVictim->userActionType = 'kill';
            $responseVictim->userActionValue = 'strike';
            $responseVictim->message = 'Пользователь ' . $this->userAuthor->name .' напал на тебя!';
            $this->addResponseMessage($responseVictim->toString());
            return;
        }

        return;
    }

    private function showMob(){
        $response = new Response($this->userAuthor);
        $response->request = $this->requestWsMessage;
        $response->message =  "Вот те монстер";
        $response->mobName = 'mob1';
        $response->mobActionType = 'create';
        $response->mobActionValue = array('mob1' => $this->getMob('mob1'));
        $this->addResponseMessage($response->toString());
    }

    private function showMap(){
        $response = new Response($this->userAuthor);
        $response->request = $this->requestWsMessage;
        $response->message = "Вот те карта";
        $response->partMap = $this->getMap();
        $this->addResponseMessage($response->toString());
    }

    private function showUsers(){
        $request = '';
        foreach ($this->usersList->getUsersList() as $user) {
            $request .= "Пользователь ".$user->name . "[$user->wsId]<br>";
        }
        $request .= "<br><hr>";

        $response = new Response($this->userAuthor);
        $response->request = $this->requestWsMessage;
        $response->message = "Сейчас в травмаде:<br>$request";
        $this->addResponseMessage($response->toString());
    }

    private function moveUser(){
        // Движение пользователя
        $response = new Response($this->userAuthor);
        $response->request = $this->requestWsMessage;

        if (!$this->map->tryMoveUser($this->requestWsMessage, $this->userAuthor)){
            $response->actionType = null;
            $response->actionValue = null;
            $response->message = "Вы не можете двигаться в этом направлении";

            $this->addResponseMessage($response->toString());
            return;
        }

        $response->actionType = 'move';
        $response->actionValue = $this->requestWsMessage;
        $response->message = "Вы двигаетесь на {$this->requestWsMessage}";

        $this->addResponseMessage($response->toString());

        // Оповестим всех, что мы двигаемся.
        $subscribers = $this->usersList->getUsersExludeAsWsId($this->userAuthor->wsId);
        $responseAll = new Response($subscribers);
        $responseAll->userName = $this->userAuthor->name;
        $responseAll->userActionType = 'move';
        $responseAll->userActionValue = $this->requestWsMessage;
        $responseAll->message = 'Пользователь ' . $this->userAuthor->name . ' двинул на ' . $this->requestWsMessage;

        $this->addResponseMessage($responseAll->toString());
    }

    private function connectNewUser(){
        $this->userAuthor = new TravmadUser($this->userAuthorWsId);
        $this->usersList->addUser($this->userAuthor);

        $response = new Response($this->userAuthor);
        $response->request = $this->requestWsMessage;
        $response->message = 'Введите имя';

        $this->addResponseMessage($response->toString());
    }


    private function authorizeUser(){
        // Присвоим имя новому пользователю
        $this->userAuthor->name = $this->requestWsMessage;
        $this->userAuthor->positionX = 4;
        $this->userAuthor->positionY = 4;
        $this->userAuthor->zone = 'example';
        $response = new Response($this->userAuthor);
        $response->request = $this->requestWsMessage;
        $response->message = "Теперь ваше имя {$this->requestWsMessage}";

        // Зададим нашу позицию и позицию остальных чаров.
        $response->actionType = 'setPosition';
        $response->actionValue = $this->getAllPosition($this->userAuthor);

        // Поместим чара в зону example
        $this->map->getZone($this->userAuthor->zone)->putChar($this->userAuthor, $this->userAuthor->positionX, $this->userAuthor->positionY);

        $this->addResponseMessage($response->toString());

        // Оповестим всех, что появился новый.
        $subscribers = $this->usersList->getUsersExludeAsWsId($this->userAuthor->wsId);
        $responseAll = new Response($subscribers);
        $responseAll->userName = $this->userAuthor->name;
        $responseAll->userActionType = 'connectChar';
        $responseAll->userActionValue = array($this->userAuthor->name => $this->userAuthor->toStringAsMob());
        $responseAll->message = 'Появился пользователь ' . $this->userAuthor->name;

        $this->addResponseMessage($responseAll->toString());
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
            $chars[$user->name] = $user->toStringAsMob();
        }

        return $chars;
    }

    private function getMap(){
        return $this->map->toString();
    }

    private function getMob($name){
        $type1 = '067-Goblin01.png'; // бобрик
        $type2 = '075-Devil01.png'; // бесенок
        $type3 = '180-Switch03.png'; // дырка
        $type4 = '151-Animal01.png'; // собака
        $type5 = '175-Chest02.png'; // ящик
        $data = '
[
    {
        "name": "'.$name.'",
        "x": 19,
        "y": 21
    },
    [
        {

            "character_hue": "'.$type1.'",
            "direction": "bottom",
            "type": "random",
            "trigger": "event_touch",
            "speed": 3,
            "frequence": 0,
            "action_battle": {
                "area": 2,
                "hp_max": 30,
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

}