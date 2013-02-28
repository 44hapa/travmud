<?php

DEFINE('BASE_PATH', __DIR__);
require_once(BASE_PATH . '/dev.config.php');

class Config{
    static private $config = array(
        'maxBufferSize' => 2048,
		'stepSize' => 4,
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
        global $devConfig;
        $result = array_merge(self::$config, $devConfig);
        return $result;
    }

}