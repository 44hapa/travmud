<?php

class Multicommand{

    /**
     *
     * @var Multicommand
     */
    static private $instance;


    /**
     * Ключи - wsId пользователя, значения - список команд пользователя
     * в виде запроса от ws сервера ($requestWsMessage)
     * @var array
     */
    private $stackCommands = array();
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

        foreach ($this->getNextStackCommand() as $command) {
            $action = new Action($command);
            $action->execute();
            $this->addResponseMessage($action->getResponseMessage());
        }
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
            $this->stackCommands[$userAuthorWsId][] = $this->createRequestFromWebsocket($userAuthorWsId, $message);
        }
    }

    private function getNextStackCommand(){
        $result = array();
        foreach ($this->stackCommands as $userWsId => $requestWsMessage) {
            $result[] = array_shift($this->stackCommands[$userWsId]);
        }
        if (empty($this->stackCommands[$userWsId])) {
            unset($this->stackCommands[$userWsId]);
        }
        return $result;
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