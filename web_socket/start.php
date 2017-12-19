<?php
define('EOF','\r\n');
require 'lib/esb_socket.class.php';
require 'lib/start.class.php';
$work = [];
$esb = new swoole_process('esb');
$esb_pid = $esb->start();
$web_socket = new swoole_process('start');
$web_socket_pid = $web_socket->start();
$work['esb'] = $esb;
$work['socket'] = $web_socket;
//子进程也会包含此事件
swoole_event_add($esb->pipe, function ($pipe) use($work){
    $data = $work['esb']->read();
    $work['socket']->write($data);
});

swoole_event_add($web_socket->pipe, function ($pipe) use($work){
    $data = $work['socket']->read();
    $work['esb']->write($data);
});