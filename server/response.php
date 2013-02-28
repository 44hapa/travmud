<?php

class Response{

    public $request;

    public $actionType;
    public $actionValue;
    public $message;

    public $mobs;
    public $users;
    public $partMap;


    public function __construct(){}

    private function getTemplate(){
        $templateUserResponse = array(
            'request' => '',
            'response' => array(
                'actionType' => null,
                'actionValue' => null,
                'message' => 'Введите ваше имя',
            ),
            'views' => array(
                'mobs' => null,
                'users' => null,
                'partMap' => null
            ),
        );
        return $templateUserResponse;
    }

    public function toString(){
        $templateUserResponse = $this->getTemplate();
        $templateUserResponse['request'] = $this->request;
        $templateUserResponse['response']['actionType'] = $this->actionType;
        $templateUserResponse['response']['actionValue'] = $this->actionValue;
        $templateUserResponse['response']['message'] = $this->message;

        $templateUserResponse['views']['mobs'] = $this->mobs;
        $templateUserResponse['views']['users'] = $this->users;
        $templateUserResponse['views']['partMap'] = $this->partMap;

        return json_encode($templateUserResponse);
    }

}