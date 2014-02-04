<?php

class Zone
{

    public $zone;
    private $longX = 30;
    private $longY = 30;

    const CELL_COVER = 'cover';
    const CELL_MOBS = 'mobs';
    const CELL_CHARS = 'chars';
    const CELL_OBJECTS = 'covers';

    public function __construct()
    {
        for ($x = 0; $x <= $this->longX; $x++) {
            for ($y = 0; $y <= $this->longY; $y++) {
                $this->zone[$x][$y][self::CELL_COVER] = 1505; // Тип покрытия
                $this->zone[$x][$y][self::CELL_MOBS] = array();
                $this->zone[$x][$y][self::CELL_CHARS] = array();
                $this->zone[$x][$y][self::CELL_OBJECTS] = array();
            }
        }
    }

    public function canMove($newX, $newY)
    {
        if (isset($this->zone[$newX][$newY])) {
            return true;
        }
        return false;
    }

    public function putCreature(CreatureAbstract $creature, $x, $y)
    {
        if ($creature instanceof TravmadUser) {
            $creatureType = self::CELL_CHARS;
        } elseif ($creature instanceof Mob) {
            $creatureType = self::CELL_MOBS;
        } else {
            throw new Exception('Неизвестная живность');
        }
        $this->zone[$x][$y][$creature][$creature->id] = $creature->name;
    }

    public function pullCreature(CreatureAbstract $creature, $x, $y)
    {
        if ($creature instanceof TravmadUser) {
            $creatureType = self::CELL_CHARS;
        } elseif ($creature instanceof Mob) {
            $creatureType = self::CELL_MOBS;
        } else {
            throw new Exception('Неизвестная живность');
        }
        unset($this->zone[$x][$y][$creatureType][$creature->id]);
    }

}
