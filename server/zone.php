<?php

class Zone {

    public $zone;

    private $longX = 30;
    private $longY = 30;

    const CELL_COVER = 'cover';
    const CELL_MOBS = 'mobs';
    const CELL_CHARS = 'chars';
    const CELL_OBJECTS = 'covers';

    public function __construct(){
        for ($x = 0; $x <= $this->longX; $x++){
            for ($y = 0; $y <= $this->longY; $y++){
                $this->zone[$x][$y][self::CELL_COVER] = 1505; // Тип покрытия
                $this->zone[$x][$y][self::CELL_MOBS] = array();
                $this->zone[$x][$y][self::CELL_CHARS] = array();
                $this->zone[$x][$y][self::CELL_OBJECTS] = array();
            }
        }
    }


    public function putChar($charId, $charName, $x, $y){
        $this->zone[$x][$y][self::CELL_CHARS][$charId] = $charName;
    }

    public function pullChar($charId, $x, $y){
        unset($this->zone[$x][$y][self::CELL_CHARS][$charId]);
    }

}