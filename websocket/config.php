<?php

class Config{
    static private $config = array(
        'maxBufferSize' => 2048,
        'websocket' => array(
            'addr' => '0.0.0.0',
            'port' => '8000',
        ),
        'server' => array(
            'addr' => '0.0.0.0',
            'port' => '8001',
        )
    );


    private function __construct() {
    }


    static public function getConfig(){
        return self::$config;
    }

}