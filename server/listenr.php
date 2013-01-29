<?php

class Listener {

    protected $maxBufferSize;
    protected $master;
    protected $sockets = array();

    function __construct($addr, $port, $bufferLength = 2048) {
        $this->maxBufferSize = $bufferLength;
        $this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("Failed: socket_create()");
        socket_connect($this->master, '0.0.0.0', '9001');
        echo("Server LISTENER started\nListening on: $addr:$port\nMaster socket: " . $this->master);
//        while (true) {
//            $read = $this->sockets;
//            $write = $except = null;
//            @socket_select($read, $write, $except, null);
//            $numBytes = @socket_recv($socket, $buffer, $this->maxBufferSize, 0);
//            echo "\n$buffer\n";
//        }
        $i = 0;
        while ($i < 30) {
            sleep(1);
            $message = socket_read($this->master, $this->maxBufferSize);
            var_dump($message);
            $i++;
        }
        socket_close($this->master);
    }
}

new Listener('0.0.0.0', '9001');

