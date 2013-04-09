<?php

class Battle{


    const FIGHTER_TYPE = 'fighterType';
    const FIGHTER_IDENT = 'fighterIdent';

    static private $instance;

    private $queue = array();
    private $delay = 4;

    /**
     *
     * @var UsersList
     */
    private $usersList;

    private $config;

    private $responseMessage;

    private function __construct() {
        $this->config = Config::getConfig();
        $this->usersList = UsersList::getInstance();
    }

    public function getResponseMessage(){
        $responseMessage = $this->responseMessage;
        $this->responseMessage = null;
        return $responseMessage;
    }

    private function addResponseMessage($responseMessage){
        $this->responseMessage .= $responseMessage;
    }

    public function execute(){
        if (empty($this->queue) || (0 != Listener::$mainCounter % $this->delay)) {
            return;
        }

        foreach ($this->queue as $fighter) {
            if (Interaction::CHAR == $fighter[self::FIGHTER_TYPE]) {
                $user = $this->usersList->getUserByWsId($fighter[self::FIGHTER_IDENT]);
                $victim = $this->usersList->getUserByWsId($user->enemyIdent);

                $damage = rand(0, 20);
                $damagePecent = $damage * 100 / $victim->maxHealth;
                $victim->curentHealth -= $damage;

                if ($victim->curentHealth <= 0) {
                    $this->killed($user, $victim);
                    return;
                }

                $response = new Response($user);
                $response->request = 'БОЙ!!!';
                $response->actionType = Interaction::STRIKE_SWORD;
                $response->actionValue = $victim->name;
                $response->userName = $victim->name;
                $response->userActionType = Interaction::GOT_DAMAGE_STRIKE_SWORD;
                $response->userActionValue = $user->name;
                $response->userMessage = "Ты снес противнику $damagePecent% жизней";
                $response->message =  "Ты отоварил {$victim->name} и снес ему $damagePecent% жизней.";
                $this->addResponseMessage($response->toString());

                $responseVictim = new Response($victim);
                $responseVictim->request = 'БОЙ!!!';
                $responseVictim->actionType = Interaction::GOT_DAMAGE_STRIKE_SWORD;
                $responseVictim->actionValue = $damagePecent;
                $responseVictim->userName = $user->name;
                $responseVictim->userActionType = Interaction::STRIKE_SWORD;
                $responseVictim->userActionValue = $victim->name;
                $responseVictim->message = "Пользователь {$user->name} охреначил тебя, на $damagePecent% жизней!";
                $this->addResponseMessage($responseVictim->toString());

                // TODO: добавить сообщение для всех, исключая уже оповещенных.
            }
        }
    }


    private function killed(TravmadUser $user, TravmadUser $victim){
        // Убийца получает сообщение о том, какой чар умер.
        $response = new Response($user);
        $response->userName = $victim->name;
        $response->userActionType = 'die';
        $response->userActionValue = 'die';
        $response->message =  "Ты УБИЛ {$victim->name}!!!";
        $this->addResponseMessage($response->toString());


        // Жертва получает сообщение, что умерла.
        $responseVictim = new Response($victim);
        $responseVictim->actionType = 'die';
        $responseVictim->actionValue = $user->name;
        $responseVictim->message = 'Пользователь ' . $user->name .' охреначил тебя ДОСМЕРТИ!!!';
        $this->addResponseMessage($responseVictim->toString());

        $this->removeFighter(Interaction::CHAR, $user->wsId);
        $this->removeFighter(Interaction::CHAR, $victim->wsId);

        // Вычеркнем противника
        $user->win();
        // Переместимся в точку возрождения и вычеркнем противника
        $victim->rip();

        // Зададим нашу позицию и позицию остальных чаров.
        $responsePosition = new Response($victim);
        $responsePosition->message = "Ты возродился";
        $responsePosition->actionType = 'setPosition';
        $responsePosition->actionValue = array('myPosition' => $victim->getAsPlayer(), 'charsPosition' => $this->usersList->toStringAsMobExclude($victim->wsId));

        $this->addResponseMessage($responsePosition->toString());

        // Оповестим всех, возродился умерший.
        $subscribers = $this->usersList->getUsersExludeAsWsId($victim->wsId);
        $responseAll = new Response($subscribers);
        $responseAll->userName = $victim->name;
        $responseAll->userActionType = 'connectChar';
        $responseAll->userActionValue = array($victim->name => $victim->toStringAsMob());
        $responseAll->message = 'Возродился пользователь ' . $victim->name;

        $this->addResponseMessage($responseAll->toString());
    }

    public function getQueue(){
        return $this->queue;
    }

    public function addFighter($fighterType, $fighterIdent){
        $fighter = array(self::FIGHTER_TYPE=> $fighterType, self::FIGHTER_IDENT => $fighterIdent);
        if (!in_array($fighter, $this->queue)) {
            array_push($this->queue, $fighter);
        }
    }

    public function removeFighter($fighterType, $fighterIdent){
        $fighter = array(self::FIGHTER_TYPE=> $fighterType, self::FIGHTER_IDENT => $fighterIdent);
        $queueId = array_search($fighter, $this->queue);
        if (false !== $queueId) {
            unset($this->queue[$queueId]);
        }
    }

    public function stopFigting(array $users){
        foreach ($users as $user) {
            $user->enemyType = null;
            $user->enemyIdent = null;
            $this->removeFighter(Interaction::CHAR, $user->wsId);
        }
    }

    /**
     *
     * @return Battle
     */
    static public function getInstance(){
        if (!empty(self::$instance)){
            return self::$instance;
        }
        self::$instance = new self();
        return self::$instance;
    }
    
}