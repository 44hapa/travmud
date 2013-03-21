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
        self::$instance->map = array('example' => $zone->zone);

        return self::$instance;
    }


    public function toString(){
        $map = array();
        foreach ($this->map as $zoneName=>$zone){
            foreach ($zone as $x => $zoneXpropertys){
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