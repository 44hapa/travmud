<?php

class Interaction{


    /**
     *
     * @var TravmadUser
     */
    private $user;


    /**
     *
     * @param TravmadUser $user
     */
    public function __construct(TravmadUser $user){
        $this->user = $user;
    }



    public function tryStryke(TravmadUser $victim){
        if (!$this->canStrike($victim)) {
            return false;
        }
        $this->strike($victim);
        return true;
    }



    /**
     *
     * @param TravmadUser $victim
     * @return boolean
     */
    private function canStrike(TravmadUser $victim){
        return true;
    }


    private function strike(TravmadUser $victim){
        
    }

}