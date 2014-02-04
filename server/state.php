<?php

class State
{

    /**
     *
     * @var State
     */
    static private $instance;
    private $delay = 8;

    /**
     *
     * @var UsersList
     */
    private $usersList;
    private $config;
    private $responseMessage;

    const ACTION_RECOVERY = 'recovery';
    const ACTION_PROMPT = 'prompt';

    private function __construct()
    {
        $this->config = Config::getConfig();
        $this->usersList = UsersList::getInstance();
    }

    public function getResponseMessage()
    {
        $responseMessage = $this->responseMessage;
        $this->responseMessage = null;
        return $responseMessage;
    }

    private function addResponseMessage($responseMessage)
    {
        dump($responseMessage);
        $this->responseMessage .= $responseMessage;
    }

    public function execute()
    {
        if ((0 == $this->usersList->getCountList()) || (0 != Listener::$mainCounter % $this->delay)) {
            return;
        }
        foreach ($this->usersList->getList() as $user) {
            if (!$user->auth) {
                break;
            }
            $this->recoveryHealth($user);

            $this->generatePrompt($user);
        }
    }

    private function recoveryHealth(TravmadUser $user)
    {
        if ($user->curentHealth >= $user->maxHealth) {
            return null;
        }
        $curent = $user->curentHealth + rand(0, 30);
        $user->curentHealth = $curent > $user->maxHealth ? $user->maxHealth : $curent;
    }

    private function generatePrompt(TravmadUser $user)
    {
        $response = new Response($user);
        $response->request = 'Текущее состояние';
        $response->actionType = self::ACTION_PROMPT;
        $response->actionValue = $user->getAsPlayer();
        $this->addResponseMessage($response->toString());
    }

    /**
     *
     * @return State
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