<?php

/**
 * Методы взаимодействия персонажей между собой (users&mobs)
 */
class Interaction
{

    const CHAR = 'char';
    const MOB = 'mob';
    const STRIKE_SWORD = 'strikeSword';
    const GOT_DAMAGE_STRIKE_SWORD = 'gotStrikeSword';

    /**
     *
     * @var TravmadUser
     */
    private $user;

    /**
     *
     * @var Battle
     */
    private $battle;

    /**
     *
     * @param TravmadUser $user
     */
    public function __construct(TravmadUser $user)
    {
        $this->battle = Battle::getInstance();
        $this->user = $user;
    }

    public function tryStryke(TravmadUser $victim)
    {
        if (!$this->canStrike($victim)) {
            return false;
        }
        $this->strike($victim);
        return true;
    }

    /**
     *
     * @param TravmadUser $victim
     * @return boolean
     */
    private function canStrike(TravmadUser $victim)
    {
        if (2 < abs($this->user->positionX - $victim->positionX) || 2 < abs($this->user->positionY - $victim->positionY)) {
            return false;
        }
        return true;
    }

    private function strike(TravmadUser $victim)
    {
        $this->user->enemyType = self::CHAR;
        $this->user->enemyIdent = $victim->wsId;
        $this->user->employment = CreatureAbstract::EMPLOYMENT_FIGHTING;

        $victim->enemyType = self::CHAR;
        $victim->enemyIdent = $this->user->wsId;
        $victim->employment = CreatureAbstract::EMPLOYMENT_FIGHTING;

        $this->battle->addFighter(self::CHAR, $this->user->wsId);
        $this->battle->addFighter(self::CHAR, $victim->wsId);
    }

}