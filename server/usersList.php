<?php

class UsersList extends CreatureListAbstract
{

    /**
     *
     * @param type $wsId
     * @return TravmadUser | null
     */
    public function getByWsId($wsId)
    {
        foreach ($this->list as $user) {
            if ($wsId == $user->wsId) {
                return $user;
            }
        }
        return null;
    }

    public function getListAsWsId()
    {
        return $this->getListAsField('wsId');
    }

    public function getListExludeAsWsId($excludeWsIds)
    {
        $excludeWsIds = (array) $excludeWsIds;
        $usersExclude = $this->getListAsWsId();
        foreach ($excludeWsIds as $wsId) {
            unset($usersExclude[$wsId]);
        }
        return $usersExclude;
    }

    public function toStringAsMob()
    {
        foreach ($this->list as $user) {
            $chars[$user->name] = $user->toStringAsMob();
        }
        return $chars;
    }

    public function toStringAsMobExclude($excludeWsIds)
    {
        if (!$subscribers = $this->getListExludeAsWsId($excludeWsIds)) {
            return null;
        }

        foreach ($subscribers as $user) {
            $chars[$user->name] = $user->toStringAsMob();
        }
        return $chars;
    }

}