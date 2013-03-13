<?php

class Action{

    private $requestAuthor;

    public function __construct($requestFromWebsocket, $usersList){
        list($userId, $message) = explode($this->config['startBuferDelimiter'], $requestFromWebsocket);
    }


}