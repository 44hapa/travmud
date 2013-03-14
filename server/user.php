<?php

class TravmadUser {

    private $config;

    public $id;
    public $name;

    /**
     * Id в терминах websocket
     * @var mixe
     */
    public $wsId;

    public $positionX;
    public $positionY;

    public function __construct($wsId){
        $this->wsId = $wsId;
        $this->config = Config::getConfig();
    }


    /**
     *
     * @param string $direction
     * @return \TravmadUser
     */
    public function move($direction){
        switch ($direction) {
            case 'север':
                $this->positionY -= 1;
                break;
            case 'юг':
                $this->positionY += 1;
                break;
            case 'запад':
                $this->positionX -= 1;
                break;
            case 'восток':
                $this->positionX += 1;
                break;

            default:
                break;
        }
        return $this;
    }


    public function toStringAsMob(){
        $canvasPositionX = $this->positionX * $this->config['stepSize'];
        $canvasPositionY = $this->positionY * $this->config['stepSize'];

        $main['name'] = $this->name;
        $main['x'] = $canvasPositionX;
        $main['y'] = $canvasPositionY;

        $options['character_hue'] = '067-Goblin01.png';
        $options['direction'] = 'bottom';
        $options['type'] = 'fixed';
        $options['trigger'] = 'event_touch';
        $options['speed'] = 1;
        $options['frequence'] = 0;

        $data = array($main, array($options));

        return json_encode($data);
    }

}