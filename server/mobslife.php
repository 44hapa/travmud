<?php

class Mobslife
{

    static private $instance;
    private $queue = array();
    private $delay = 2;
    private $responseMessage;

    /**
     *
     * @var MobList
     */
    private $mobList;

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

    private function __construct()
    {
        $this->mobList = MobList::getInstance();
        $this->usersList = UsersList::getInstance();
        $this->map = Map::getInstance();
    }

    public function getResponseMessage()
    {
        $responseMessage = $this->responseMessage;
        $this->responseMessage = null;
        return $responseMessage;
    }

    private function addResponseMessage($responseMessage)
    {
        $this->responseMessage .= $responseMessage;
    }

    public function execute()
    {
        // TODO: пока очередь - это все мобы. Потом буду думать..
        $this->queue = $this->mobList->getList();

        if (empty($this->queue) || (0 != Listener::$mainCounter % $this->delay)) {
            return;
        }

        foreach ($this->queue as $mob) {
            /* @var $mob Mob */
            $this->move($mob);
        }
    }

    private function move(CreatureAbstract $creature)
    {

        $compass = array('север', 'юг', 'запад', 'восток');
        $direction = rand(0, 3);

        if (!$this->map->tryMoveCreature($compass[$direction], $creature)) {
            // Не пойдет туда моб
            return;
        }

        // Оповестим всех, что моб двигаеется.
        $subscribers = $this->usersList->getList();
        $responseAll = new Response($subscribers);
        $responseAll->mobName = $creature->name;
        $responseAll->mobActionType = 'move';
        $responseAll->mobActionValue = array($creature->name => $compass[$direction]);
        $responseAll->message = 'Моб ' . $creature->name . ' двинул на ' . $compass[$direction];

        $this->addResponseMessage($responseAll->toString());
    }

    /**
     *
     * @return Mobslife
     */
    static public function getInstance()
    {
        if (!empty(self::$instance)) {
            return self::$instance;
        }
        self::$instance = new self();
        return self::$instance;
    }

}