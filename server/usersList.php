<?php

class UsersList{


    private $usersList = array();


    static private $instance;



    private function __construct() {
    }


    static public function getInstance(){
        if (!empty(self::$instance)){
            return self::$instance;
        }
        self::$instance = new self();
        return self::$instance;
    }


    public function addUser(TravmadUser $user){
        $this->usersList[] = $user;
    }

    public function getUserByWsId($wsId){
        foreach ($this->usersList as $user) {
            if ($wsId == $user->wsId) {
                return $user;
            }
        }
        return null;
    }

    public function getUsersList(){
        return $this->usersList;
    }

    public function getUsersAsName(){
        return $this->getUsersAsField('name');
    }

    public function getUsersAsWsId(){
        return $this->getUsersAsField('wsId');
    }

    private function getUsersAsField($field){
        $usersAsField = array();
        foreach ($this->usersList as $user){
            $usersAsField[$user->{$field}] = $user;
        }
        return $usersAsField;
    }

}