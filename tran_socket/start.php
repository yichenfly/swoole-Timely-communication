<?php
define('EOF','\r\n');
$client = new swoole_client(SWOOLE_SOCK_TCP|SWOOLE_SSLv23_METHOD, SWOOLE_SOCK_ASYNC);
$client->chan = new Swoole\Channel(1024 * 256);
$client->lock = new swoole_lock(SWOOLE_MUTEX);
$client->on("connect", function($cli) {
    swoole_timer_tick(5000, function($timer_id,$cli){
        $cli->send('keep live'.EOF);
    },$cli);
    swoole_timer_tick(50, function($timer_id,$cli){
        $data = $cli->chan->pop();
        if($data&&$cli->lock->trylock())
        {
            $data = array('class'=>'transaction','method'=>'transaction','data'=>array('id'=>$data));
            $data = json_encode($data,JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_NUMERIC_CHECK|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_FORCE_OBJECT|JSON_PRESERVE_ZERO_FRACTION|JSON_UNESCAPED_UNICODE|JSON_PARTIAL_OUTPUT_ON_ERROR);
            $cli->send($data);
        }else{
            $lock->unlock();
        }
    },$cli);
});

$client->on("receive", function($cli, $data){
    if(!isset($data))
    {
        return 0;
    }
    $data = json_decode(explode(EOF,$data)[0],true);
    switch($data['method'])
    {
        case 'put':
            $cli->chan->push($data['data']['id']);
            return 0;
        break;
        case 'check':
            $data = $cli->chan->pop();
            if($data)
            {
                $data = array('class'=>'transaction','method'=>'transaction','data'=>array('id'=>$data));
                $data = json_encode($data,JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_NUMERIC_CHECK|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_FORCE_OBJECT|JSON_PRESERVE_ZERO_FRACTION|JSON_UNESCAPED_UNICODE|JSON_PARTIAL_OUTPUT_ON_ERROR);
                $cli->send($data);
            }else{
                $cli->lock->unlock();
            }
            return 0;
        break;
    }
});

$client->on("close", function($cli){
    echo "close\n";
});

$client->on("error", function($cli){
    exit("error\n");
});

$client->connect('192.168.101.4', 9506, 0.5);