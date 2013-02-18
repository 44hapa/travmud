#! /usr/local/bin/php
<?php
require_once('./websockets.php');

class echoServer extends WebSocketServer {


    private $userIncId = 0;

    //protected $maxBufferSize = 1048576; //1MB... overkill for an echo server, but potentially plausible for other applications.

    protected function process($user, $message) {
//        if ('UTF-8' != mb_detect_encoding($message)) {
//            throw new Exception('Encoding is not UTF-8 ' . mb_detect_encoding($message));
//        }

        $this->sendToListener("USER_ID: {$user->id} $message\n");
    }


    protected function sendToUsers($listenerBufer){
        list($usersString, $message) = explode('__', trim($listenerBufer));

        $users = explode('_', $usersString);

        foreach ($users as $userId) {
            $this->sendToUser($this->getUserById($userId), $message);
        }
    }

    protected function sendToUsers_nc($listenerBufer){
        $listenerBufer = trim($listenerBufer);
//        $listenerBufer = json_encode($listenerBufer);
//        echo("\n$listenerBufer");
//        die();

//        $messagesJSON = '1_2__{"request":{"actionType":"move","direction":"north"},"response":{"actionType":"move","action":"'.$listenerBufer.'","message":"Ты идешь на '.$listenerBufer.'"},"views":{"mobs":[],"users":[],"partMap":[]}}';
        $messagesJSON = '1__{"request":{"actionType":"move","direction":"north"},"response":{"actionType":"move1","action":"'.$listenerBufer.'","message":"Ты идешь на '.$listenerBufer.'"},"views":{"mobs":[],"users":[],"partMap":[]}}';
        list($usersString, $message) = explode('__', $messagesJSON);

        $users = explode('_', $usersString);

        foreach ($users as $userId) {
            $this->sendToUser($this->getUserById($userId), $message);
        }
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