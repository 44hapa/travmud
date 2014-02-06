<?php

require_once('./../config.php');
require_once(BASE_PATH . '/server/zone.php');

class Map
{

    public $map = array();

    /**
     *
     * @var Map
     */
    static private $instance;

    private function __construct()
    {
        
    }

    /**
     *
     * @param string $zoneName
     * @return Zone
     */
    public function getZone($zoneName)
    {
        return $this->map[$zoneName];
    }

    /**
     *
     * @param string $direction
     * @param TravmadUser $user
     * @return boolean
     */
    public function tryMoveCreature($direction, CreatureAbstract $creature)
    {
        /* @var Zone $zone */
        $zone = $this->map[$creature->zone];
        switch ($direction) {
            case 'север':
                $result = $this->moveCreature($creature, $zone, $creature->positionX, $creature->positionY - 1);
                break;
            case 'юг':
                $result = $this->moveCreature($creature, $zone, $creature->positionX, $creature->positionY + 1);
                break;
            case 'запад':
                $result = $this->moveCreature($creature, $zone, $creature->positionX - 1, $creature->positionY);
                break;
            case 'восток':
                $result = $this->moveCreature($creature, $zone, $creature->positionX + 1, $creature->positionY);
                break;

            default:
                $result = false;
                break;
        }
        return $result;
    }

    private function catMove(Zone $zone, $newX, $newY)
    {
        return $zone->canMove($newX, $newY);
    }

    /**
     *
     * @param CreatureAbstract $creature
     * @param Zone $zone
     * @param int $newX
     * @param int $newY
     * @return boolean
     */
    private function moveCreature(CreatureAbstract $creature, Zone $zone, $newX, $newY)
    {
        if ($this->catMove($zone, $newX, $newY)) {
            $zone->pullCreature($creature, $creature->positionX, $creature->positionY);
            $zone->putCreature($creature, $newX, $newY);

            $creature->positionX = $newX;
            $creature->positionY = $newY;

            return true;
        }
        return false;
    }

    /**
     *
     * @return Map
     */
    static public function getInstance()
    {
        if (!empty(self::$instance)) {
            return self::$instance;
        }
        self::$instance = new self();
        $zone = new Zone();
        self::$instance->map = array('example' => $zone);

        return self::$instance;
    }

    public function toString()
    {
        $map = array();
        foreach ($this->map as $zoneName => $zone) {
            foreach ($zone->zone as $x => $zoneXpropertys) {
                foreach ($zoneXpropertys as $y => $cellProperty) {
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
                1505 => array(0, 0),
            )
        );

        return json_encode($result);
    }

}