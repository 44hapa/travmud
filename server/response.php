<?php

require_once('./../config.php');

class Response
{

    public $request = null;
    public $actionType = null;
    public $actionValue = null;
    public $message = null;
    public $partMap = null;
    public $userName = null;
    public $userActionType = null;
    public $userActionValue = null;
    public $userMessage = null;
    public $mobName = null;
    public $mobActionType = null;
    public $mobActionValue = null;
    public $mobMessage = null;
    public $config;
    private $subscribers;

    /**
     *
     * @param TravmadUser | array  $subscribers
     */
    public function __construct($subscribers)
    {
        $this->subscribers = is_object($subscribers) ? array($subscribers) : $subscribers;
        $this->config = Config::getConfig();
    }

    private function getSubscribersWsIds()
    {
        $usersWsIds = array();
        foreach ($this->subscribers as $user) {
            $usersWsIds[] = $user->wsId;
        }
        return implode($this->config['userDelimiter'], $usersWsIds);
    }

    private function getTemplate()
    {
        $templateUserResponse = array(
            'request' => null,
            'actionType' => null,
            'actionValue' => null,
            'message' => null,
            'partMap' => null,
            'user' => array(
                'name' => null,
                'actionType' => null,
                'actionValue' => null,
                'message' => null
            ),
            'mob' => array(
                'name' => null,
                'actionType' => null,
                'actionValue' => null,
                'message' => null
            )
        );
        return $templateUserResponse;
    }

    public function toString()
    {
        if (empty($this->subscribers)) {
            return null;
        }

        $templateUserResponse = $this->getTemplate();
        $templateUserResponse['request'] = $this->request;
        $templateUserResponse['actionType'] = $this->actionType;
        $templateUserResponse['actionValue'] = $this->actionValue;
        $templateUserResponse['message'] = $this->message;
        $templateUserResponse['partMap'] = $this->partMap;

        $templateUserResponse['user']['name'] = $this->userName;
        $templateUserResponse['user']['actionType'] = $this->userActionType;
        $templateUserResponse['user']['actionValue'] = $this->userActionValue;
        $templateUserResponse['user']['message'] = $this->userMessage;

        $templateUserResponse['mob']['name'] = $this->mobName;
        $templateUserResponse['mob']['actionType'] = $this->mobActionType;
        $templateUserResponse['mob']['actionValue'] = $this->mobActionValue;
        $templateUserResponse['mob']['message'] = $this->mobMessage;

        return $this->getSubscribersWsIds() . $this->config['startBuferDelimiter'] . json_encode($templateUserResponse) . $this->config['endBuferDelimiter'];
    }

}