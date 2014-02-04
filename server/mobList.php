<?php

class MobList extends CreatureListAbstract
{

    protected function __construct()
    {
        parent::__construct();
        // Загружаем всех мобов (потом будет из БД)
        $this->loadAllMobs();
    }

    private function loadAllMobs()
    {
        // Тут должно быть обращение к БД, создание обьектов mob, наполнение листа..
        $mob = new Mob();
        $mob->name = 'defeultMobName';
        $mob->positionX = 4;
        $mob->positionY = 4;
        $mob->zone = 'example';

        $this->list[] = $mob;
    }

}
