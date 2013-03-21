<?php

require_once('./../config.php');
require_once(BASE_PATH . '/server/zone.php');

class Map {

    public $map = array();

    /**
     *
     * @var Map
     */
    static private $instance;


    private function __construct() {
    }


    /**
     *
     * @param string $zoneName
     * @return Zone
     */
    public function getZone($zoneName){
        return $this->map[$zoneName];
    }


    /**
     *
     * @param string $direction
     * @param TravmadUser $user
     * @return boolean
     */
    public function tryMoveUser($direction, TravmadUser $user){
        /*@var Zone $zone*/
        $zone = $this->map[$user->zone];
        switch ($direction) {
            case 'север':
                $result = $this->moveUser($user, $zone, $user->positionX, $user->positionY - 1);
                break;
            case 'юг':
                $result = $this->moveUser($user, $zone, $user->positionX, $user->positionY + 1);
                break;
            case 'запад':
                $result = $this->moveUser($user, $zone, $user->positionX - 1, $user->positionY);
                break;
            case 'восток':
                $result = $this->moveUser($user, $zone, $user->positionX + 1, $user->positionY);
                break;

            default:
                $result = false;
                break;
        }
        return $result;
    }

    private function catMove(Zone $zone, $newX, $newY){
        return $zone->canMove($newX, $newY);
    }

    /**
     *
     * @param TravmadUser $user
     * @param Zone $zone
     * @param int $newX
     * @param int $newY
     * @return boolean
     */
    private function moveUser(TravmadUser $user, Zone $zone, $newX, $newY){
        if ($this->catMove($zone, $newX, $newY)){
            $zone->pullChar($user, $user->positionX, $user->positionY);
            $zone->putChar($user, $newX, $newY);

            $user->positionX = $newX;
            $user->positionY = $newY;

            return true;
        }
        return false;
    }

    /**
     *
     * @return Map
     */
    static public function getInstance(){
        if (!empty(self::$instance)) {
            return self::$instance;
        }
        self::$instance = new self();
        $zone = new Zone();
        self::$instance->map = array('example' => $zone);

        return self::$instance;
    }


    public function toString(){
        $map = array();
        foreach ($this->map as $zoneName=>$zone){
            foreach ($zone->zone as $x => $zoneXpropertys){
                foreach ($zoneXpropertys as $y => $cellProperty){
                    $map[$x][$y] = array(
                        0 => $cellProperty[Zone::CELL_COVER],
                        1 => null,
                        2 => null,
                    );
                }
            }
        }

        $result = array(
            'map' => $map,
            'propreties' => array(
                150 => array(0, 15),
                1505 => array(0,0),
            )
        );

        return json_encode($result);
    }

}