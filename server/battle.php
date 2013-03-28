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

    private $messageOne;
    private $messageMass;

    private function __construct() {
        $this->config = Config::getConfig();
        $this->usersList = UsersList::getInstance();
    }


    public function execute(){
        if (empty($this->queue) || (0 != Listener::$mainCounter % $this->delay)) {
            return;
        }

        foreach ($this->queue as $fighter) {
            if (Interaction::CHAR == $fighter[self::FIGHTER_TYPE]) {
                $user = $this->usersList->getUserByWsId($fighter[self::FIGHTER_IDENT]);
                $victim = $this->usersList->getUserByWsId($user->enemyIdent);

                $damage = rand(0, 50);
                $victim->health -= $damage;

                if ($victim->health <= 0) {
                    $response = new Response();
                    $response->request = 'БОЙ!!!';
                    $response->actionType = 'killed';
                    $response->actionValue = $victim->name;
                    $response->userName = $victim->name;
                    $response->userActionType = 'die';
                    $response->userActionValue = 'die';
                    $response->message =  "Ты УБИЛ {$victim->name}!!!";
                    $this->messageOne .= $user->wsId . $this->config['startBuferDelimiter'] . $response->toString();

                    $responseVictim = new Response();
                    $responseVictim->actionType = 'die';
                    $responseVictim->actionValue = $user->name;
                    $responseVictim->userName = $user->name;
                    $responseVictim->message = 'Пользователь ' . $user->name .' охреначил тебя ДОСМЕРТИ!!!';
                    $this->messageOne .= $victim->wsId . $this->config['startBuferDelimiter'] . $responseVictim->toString();

                    $user->enemyType = null;
                    $user->enemyIdent = null;

                    $victim->enemyType = null;
                    $victim->enemyIdent = null;

                    $this->removeFighter(Interaction::CHAR, $user->wsId);
                    $this->removeFighter(Interaction::CHAR, $victim->wsId);
                    return;
                }

                $response = new Response();
                $response->request = 'БОЙ!!!';
                $response->actionType = Interaction::STRIKE_SWORD;
                $response->actionValue = $victim->name;
                $response->userName = $victim->name;
                $response->userActionType = Interaction::GOT_DAMAGE_STRIKE_SWORD;
                $response->userActionValue = $user->name;
                $response->userMessage = "У {$victim->name} осталось {$victim->health} жизней.";
                $response->message =  "Ты отоварил {$victim->name} и снес ему $damage жизней.";
                $this->messageOne .= $user->wsId . $this->config['startBuferDelimiter'] . $response->toString();

                $responseVictim = new Response();
                $responseVictim->request = 'БОЙ!!!';
                $responseVictim->actionType = Interaction::GOT_DAMAGE_STRIKE_SWORD;
                $responseVictim->actionValue = $user->name;
                $responseVictim->userName = $user->name;
                $responseVictim->userActionType = Interaction::STRIKE_SWORD;
                $responseVictim->userActionValue = $victim->name;
                $responseVictim->message = 'Пользователь ' . $user->name .' охреначил тебя, на '.$damage.' жизней!';
                $this->messageOne .= $victim->wsId . $this->config['startBuferDelimiter'] . $responseVictim->toString();

                // TODO: добавить сообщение для всех, исключая уже оповещенных.
            }
        }
    }

    public function getMessagesOne(){
        $messageOne = $this->messageOne;
        $this->messageOne = null;
        return $messageOne;
    }
    
    public function getMessagesMass(){
        $messageMass = $this->messageMass;
        $this->messageMass = null;
        return $messageMass;
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