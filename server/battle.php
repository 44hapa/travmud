<?php

class Battle{

    
    static private $instance;

    private function __construct() {
    }


    public function execute(){
    }


    public function getMessagesPersonal(){
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