<?php

abstract class CreatureAbstract
{

    public $id;
    public $name;
    public $positionX;
    public $positionY;
    public $zone;
    public $enemyType;
    public $enemyIdent;
    public $curentHealth = 150;
    public $maxHealth = 200;
    // Место реморта
    public $bornPositionX = 1;
    public $bornPositionY = 1;
    public $bornZone = 'example';

    public function rip()
    {
        $this->enemyType = null;
        $this->enemyIdent = null;

        $this->positionX = $this->bornPositionX;
        $this->positionY = $this->bornPositionY;
        $this->zone = $this->bornZone;
    }

    public function win()
    {
        $this->stopFight();
    }

    public function stopFight()
    {
        $this->enemyType = null;
        $this->enemyIdent = null;
    }

    /**
     * Данные для отображения окружающих тебя чаров.
     * TODO: пока все выглядят как бобрики.
     *
     * @return string
     */
    public function toStringAsMob()
    {
        $config = Config::getConfig();
        $canvasPositionX = $this->positionX * $config['stepSize'];
        $canvasPositionY = $this->positionY * $config['stepSize'];

        $main['name'] = $this->name;
        $main['x'] = $canvasPositionX;
        $main['y'] = $canvasPositionY;

        $options['character_hue'] = '067-Goblin01.png'; // текстура
        $options['direction'] = 'bottom';
        $options['type'] = 'fixed'; // если random - будет бегать сам
        $options['trigger'] = 'event_touch';
        $options['speed'] = 1;
        $options['frequence'] = 0;

        $action_battle['area'] = 2;
        $action_battle['hp_max'] = 300;
        $action_battle['animation_death'] = 'Darkness 1'; // картинка умирания
        $action_battle['actions'] = array('attack_ennemy');

        $options['action_battle'] = $action_battle;

        $data = array($main, array($options));

        return json_encode($data);
    }

    public function getAsPlayer()
    {
        $player['positionX'] = $this->positionX;
        $player['positionY'] = $this->positionY;
        $player['curentHealth'] = $this->curentHealth;
        $player['maxHealth'] = $this->maxHealth;

        return $player;
    }

}