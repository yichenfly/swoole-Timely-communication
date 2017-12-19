<?php
class base
{
	static protected $self;
	private function __construct($config,$serv)
	{
		$this->fd = '';
		$this->config = $config;
		$this->serv = $serv;
	}
	static function get_new($config=false,$serv=false)
	{
		if(isset(self::$self)&&!empty(self::$self))
		{
			return self::$self;
		}
		self::$self = new self($config,$serv);
		return self::$self;
	} 
	function get_redis($i = false)
	{
		$i = $i==false?1:$i++;
		if($i==3)
		{
			return false;
		}
		if(!isset($this->redis))
		{
			$this->redis = new Redis();
		}
		try{
			$this->redis->ping();
			return $this->redis;
		}catch(Exception $e){
			$this->redis->connect($this->config->redis152['host'], $this->config->redis152['port']);
			$this->redis->auth($this->config->redis152['password']);
			$this->log('3','redis error');
			return $this->get_redis($i);
		}
	}
	function log($leve=0,$msg='',$data='')
	{

	}
	function get_ip($fd)
	{
		$ip = $this->serv->connection_info($fd);
		if($ip)
		{
			return $ip['remote_ip'];
		}
		return false;
	}
	function check_auth()
	{
		
		$ip = $this->get_ip($this->fd);
		$redis = $this->get_redis();
        $redis->select(8);
        return $redis->SISMEMBER('server',$ip);
    }
    function register_server($data)
    {
        if(isset($data['server'])&&!empty($data['server']))
		{
			$redis = $this->get_redis();
            $redis->select(8);
            $redis->sadd('server',$this->get_ip($this->fd));
            $redis->hmset($this->get_ip($this->fd),array('server'=>$data['server'],'type'=>$data['type'],'status'=>$data['status'],'fd'=>$this->fd));
            $redis->sadd('server_'.$data['type'],$this->get_ip($this->fd));
            return true;
        }
        return false;
    }
    function check_data($data)
    {
        return isset($data['class'])&&isset($data['method']);
    }
	static function json($data)
	{
		return json_encode($data,JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_NUMERIC_CHECK|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_FORCE_OBJECT|JSON_PRESERVE_ZERO_FRACTION|JSON_UNESCAPED_UNICODE|JSON_PARTIAL_OUTPUT_ON_ERROR);
	}
}