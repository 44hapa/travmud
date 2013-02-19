<?php

class TravmadUser {


    public $id;
    public $name;

    /**
     * Id в терминах websocket
     * @var mixe
     */
    public $wsId;



    public function __construct($wsId){
        $this->wsId = $wsId;
    }

}