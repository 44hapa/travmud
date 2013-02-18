<?php

class Listener {

    protected $maxBufferSize;
    protected $master;
    protected $sockets = array();

    function __construct($addr, $port, $bufferLength = 2048) {
        $this->maxBufferSize = $bufferLength;
        $this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("Failed: socket_create()");
        // Коннект к websocketServer (меня там знают как listener)
        socket_connect($this->master, '0.0.0.0', '8001');
        echo("Server LISTENER started\nListening on: $addr:$port\nMaster socket: " . $this->master);

        $i = 0;
        while ($i < 30) {
            sleep(1);
            // Читаем, что там нам положил в сокет websocketServer
            $requestFromWebsocket = trim(socket_read($this->master, $this->maxBufferSize));

            // Пишем в websocketServer (он там дальше проксирует на клиента)
            $responseToWebsocket = $this->generateResponse($requestFromWebsocket);
            socket_write($this->master, $responseToWebsocket);
            var_dump($requestFromWebsocket);
            $i++;
        }
        socket_close($this->master);
    }


    public function generateResponse($requestFromWebsocket){
        $messagesJSON = '1__{"request":{"actionType":"move","direction":"north"},"response":{"actionType":"move1","action":"'.$requestFromWebsocket.'","message":"Ты идешь на '.$requestFromWebsocket.'"},"views":{"mobs":[],"users":[],"partMap":[]}}';
        return $messagesJSON;
    }

}

new Listener('0.0.0.0', '8001');

