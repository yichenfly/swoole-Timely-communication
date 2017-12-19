<?php
$config = require 'lib/config.class.php';
define('EOF','\r\n');
$serv = new swoole_server("192.168.101.4", 443, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
$serv->addlistener("192.168.101.4", 9506,SWOOLE_SOCK_TCP);
$serv->set(array(
    'worker_num' 		=> 	6,    //worker process num
    'backlog' 			=> 	128,   //listen backlog
    'max_request' 		=> 	50,
    'dispatch_mode'		=>	3, 
    'task_worker_num'	=>	15,
    'ssl_cert_file' => 'ssl/server.crt',
	'ssl_key_file' => 'ssl/server.key',
	'open_eof_check' => true, //打开EOF检测
	'package_eof' => EOF, //设置EOF
	'package_max_length'=>1024*1024*4
));

$serv->on('WorkerStart', function ($serv, $worker_id){
	require 'lib/task.class.php';
	require 'lib/base.class.php';
	require 'lib/user.class.php';
	require 'lib/quotes.class.php';
});

$serv->on('receive', function ($serv, $fd, $from_id, $data) use($config) {
    var_dump($data);
	$data = explode(EOF,$data)[0];
	$serv->send($fd,$data.EOF);
	if($data=='keep live')
	{
		return 0;
	}
	$base = base::get_new($config,$serv);
	$base->fd = $fd;
	@$data = json_decode($data,true);
	if(!$base->check_auth())
	{
        if($base->register_server($data))
        {
            return true;
        }
		return $serv->close($fd,true);
    }
    if(!$base->check_data($data))
    {
        return 0;
    }
    switch($data['class'])
	{
		case 'quotes':
			$serv->send($fd,quotes_class::get_new()->select($data['method'],$data).EOF);
        break;
        case 'transaction':
            $serv->send($fd,transaction_class::get_new()->select($data['method'],$data).EOF);
        break;
		case 'user':
			$serv->send($fd,user_class::get_new()->select($data['method'],$data).EOF);
		break;
		case 'task':
			$serv->task($data);
		break;
	}
});

$serv->on('task',function($serv,$task_id,$src_worker_id,$data) use ($config) {
	$task = task_class::get_new();
	switch($data['method'])
	{
		case 'sql':
			return $task->sql($data['sql'],$data['data']);
        break;
		case 'create_contract_buyer':
			return $task->create_contract_buyer($data['data']);
		break; 
		case 'create_contract_seller':
			return $task->create_contract_seller($data['data']);
		break;
	}
});

$serv->on('Finish',function($serv, $task_id,$data){
    $redis = base::get_new()->get_redis();
    $redis->select(3);
    $fd = $redis->get($task_id);
	switch($data['methods'])
	{
		case 'normal':
			$serv->send($fd,base::json($data['data']).EOF);
		break;
		case 'no_send_log':
		break;
		case 'transaction':
			$redis->select(8);
			$server_fd = $redis->hget($redis->SRANDMEMBER('server_3'),'fd');
			$serv->send($server_fd,base::json($data['data']).EOF);
		break;
	}
});

$serv->start();