<?php
function esb(swoole_process $process)
{
    $client = new swoole_client(SWOOLE_SOCK_TCP|SWOOLE_SSLv23_METHOD, SWOOLE_SOCK_ASYNC); //异步非阻塞
    $client->process = $process;
    $client->on('connect',function(){

    });

    $client->on("receive", function($cli,$data){
        $data = explode(EOF,$data)[0];
        $cli->process->write($data);
    });

    $client->on("error", function($cli){
        echo("error\n");
    });

    $client->on("close", function($cli){
        echo "connection is closed\n";
    });

    swoole_event_add($client->process->pipe, function ($pipe) use ($client){
        $data = $client->process->read();
        $client->send($data.EOF);
    });
    
    $client->connect('192.168.101.4', 9506, 0.5);
}