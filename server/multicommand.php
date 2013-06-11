<?php

class Multicommand{

    /**
     *
     * @var Multicommand
     */
    static private $instance;


    private $stackCommands;
    private $config;
    private $responseMessage;

    private function __construct(){
        $this->config = Config::getConfig();
    }

    public function execute(){
        // Если стек команд пустой.
        if (!$this->stackCommands) {
            return;
        }

        $action = new Action($this->getNextStackCommand());
        $action->execute();
        $this->addResponseMessage($action->getResponseMessage());
    }

    public function getResponseMessage(){
        $responseMessage = $this->responseMessage;
        $this->responseMessage = null;
        return $responseMessage;
    }

    private function addResponseMessage($responseMessage){
        $this->responseMessage .= $responseMessage;
    }

    public function setUserCommands($userAuthorWsId, $requestWsMessage){
        $requestArray = explode($this->config['multicommandDelimiter'], $requestWsMessage);
        foreach ($requestArray as $message) {
            $this->stackCommands[] = $this->createRequestFromWebsocket($userAuthorWsId, $message);
        }
    }

    private function getNextStackCommand(){
        return array_shift($this->stackCommands);
    }

    public function getStackCommands(){
        return $this->stackCommands;
    }


    /**
     * Из Id вебсокета и сообщения от клиента собираем
     * эмуляцию "запроса сервера".
     *
     * @param int $userAuthorWsId
     * @param string $requestWsMessage
     */
    private function createRequestFromWebsocket($userAuthorWsId, $requestWsMessage){
        return $userAuthorWsId . $this->config['startBuferDelimiter'] . $requestWsMessage;
    }

    /**
     *
     * @return Multicommand
     */
    static public function getInstance(){
        if (!empty(self::$instance)){
            return self::$instance;
        }
        self::$instance = new self();
        return self::$instance;
    }

}