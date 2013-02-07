#! /usr/local/bin/php
<?php
require_once('./websockets.php');

class echoServer extends WebSocketServer {


    private $userIncId = 0;

    //protected $maxBufferSize = 1048576; //1MB... overkill for an echo server, but potentially plausible for other applications.

    protected function process($user, $message) {
        $this->sendToListener("USER_ID: {$user->id} $message\n");
    }


    protected function sendToUsers($listenerBufer){

//        $listenerBufer = array (
//            1 => array(
//                'request' => array(
//                    'actionType' => 'move',
//                    'direction' => 'north',
//                ),
//                'response' => array(
//                    'move' => true,
//                    'message' => 'you go to the north',
//                ),
//                'views' => array(
//                    'mobs' => array(),
//                    'users' => array(),
//                    'partMap' => array(),
//                ),
//            )
//        );
//
//        $listenerBufer = json_encode($listenerBufer);
//        echo("\n$listenerBufer");
//        die();

        $listenerBufer = '{"1":{"request":{"actionType":"move","direction":"north"},"response":{"move":true,"message":"you go to the north"},"views":{"mobs":[],"users":[],"partMap":[]}}}';

        $mesagesForUsers = json_decode($listenerBufer, true);

        foreach ($mesagesForUsers as $userId => $message) {
            $this->sendToUser($this->getUserById($userId), json_encode($message));
        }

//        foreach ($this->users as $user) {
//            $this->sendToUser($user, json_encode($mesagesForUsers));
//        }
    }


    protected function connected($user) {
        $user->id = ++$this->userIncId;
        // Do nothing: This is just an echo server, there's no need to track the user.
        // However, if we did care about the users, we would probably have a cookie to
        // parse at this step, would be looking them up in permanent storage, etc.
    }

    protected function closed($user) {
        // Do nothing: This is where cleanup would go, in case the user had any sort of
        // open files or other objects associated with them.  This runs after the socket
        // has been closed, so there is no need to clean up the socket itself here.
    }

}

$echo = new echoServer();