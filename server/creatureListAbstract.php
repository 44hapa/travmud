<?php

abstract class CreatureListAbstract
{

    protected $list = array();
    static private $instances;
    static protected $id = 0;

    protected function __construct()
    {

    }

    final static public function getInstance()
    {
        $class = get_called_class();
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class;
        }
        return self::$instances[$class];
    }

    public function add(CreatureAbstract $creature)
    {
        $creature->id = self::$id++;
        $this->list[] = $creature;
    }

    public function getByName($name)
    {
        foreach ($this->list as $user) {
            if ($name == $user->name) {
                return $user;
            }
        }
        return null;
    }

    public function getList()
    {
        return $this->list;
    }

    public function getCountList()
    {
        return count($this->list);
    }

    public function getListAsName()
    {
        return $this->getListAsField('name');
    }

    protected function getListAsField($field)
    {
        $usersAsField = array();
        foreach ($this->list as $user) {
            $usersAsField[$user->{$field}] = $user;
        }
        return $usersAsField;
    }

}