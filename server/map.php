<?php

class Map extends SplFixedArray{

    const COUNT_FILED_OF_CELL = 5;

    const X_COORDINAT = 0;
    const Y_COORDINAT = 1;
    const ID_MOBS = 2;
    const ID_USERS = 3;
    const ID_STUFF = 4;

    /**
     *
     * @var Map
     */
    static private $instance;

    /**
     *
     * @return Map
     */
    static public function getInstance($xCount = 10, $yCount = 10){
        if (!empty(self::$instance)) {
            return self::$instance;
        }
        self::$instance = new self($count = $xCount * $yCount);
        $x = 0;
        $y = 0;

        for ($i = 0; $i<$count; $i++){
            $cell = new SplFixedArray(self::COUNT_FILED_OF_CELL);
            $cell[self::X_COORDINAT] = $x;
            $cell[self::Y_COORDINAT] = $y;
            $cell[self::ID_MOBS] = '1,2,3';
            $cell[self::ID_USERS] = null;
            $cell[self::ID_STUFF] = '4,5,6';
            self::$instance[$i] = $cell;
            
            $x++;
            if ($x >= $xCount){
                $x = 0;
                $y++;
            }
        }

        return self::$instance;
    }

}