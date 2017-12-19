<?php
function start(swoole_process $process)
{
    $web_socket = new swoole_websocket_server("0.0.0.0", 9501);
    $lock = new swoole_lock(SWOOLE_MUTEX);
    $web_socket->lock = $lock;
    $web_socket->process = $process;
    $web_socket->set(array(
        'task_worker_num'=>2,
        'worker_num'=>4,
        'dispatch_mode'=>1
    ));
    $web_socket->on('WorkerStart',function(swoole_server $server, int $worker_id) use ($web_socket){
        if(!$server->taskworker&&$server->lock->trylock()){
            swoole_event_add($server->process->pipe, function ($pipe) use ($web_socket){
                $data = $web_socket->process->read();
                $data = json_decode(explode(EOF,$data)[0],true);
                $web_socket->push($data['fd'],$data['data']);
            });
        }
        require 'lib/work.class.php';
        require 'lib/task.class.php';
    });

    $web_socket->on('open', function ($server, $request) {

    });

    $web_socket->on('message', function ($server, $frame) {

        if($frame->data=='reload'){
            $server->push($frame->fd, "reload");
        }

        $server->process->write(json_encode(array('fd'=>$frame->fd,'data'=>'ssssss')));
        
    });

    $web_socket->on('close', function ($ser, $fd) {
    });

    $web_socket->on('task',function($serv,$task_id,$src_worker_id,$data){
    });
    
    $web_socket->on('finish',function($serv,$task_id,$data){
    });

    $web_socket->start();
}