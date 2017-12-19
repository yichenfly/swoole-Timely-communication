<?php
class transaction
{
    static protected $self;
    private function __construct()
    {}
    static function get_new()
    {
        if(isset(self::$self)&&!empty(self::$self))
        {
            return self::$self;
        } 
        self::$self = new self();
        return self::$self;
    }
    function select($method,$data)
    {
        switch($method)
        {
            case 'create_contract':
                return base::json($this->create_contract($data['data']));
            break;
            case 'set_contract':
                return base::json($this->set_contract($data['data']));
            break;
        }
    }
    function transaction($data)
    {
        


    }

    function create_contract($data)
    {
        /*
        time:服务器时间
        type:1买入，2卖出
        price:money
        uid:用户id
        pid:交易产品
        id:订单id
        */
        if(isset($data['data']['type'])&&isset($data['data']['price'])&&isset($data['data']['uid'])&&isset($data['data']['pid'])&&$data['data']['price']>0)
        {
            $task_id = base::get_new()->serv->task(array('method'=>'create_contract','data'=>$data));
            $redis = base::get_new()->get_redis();
            $redis->select(3);
            $redis->set($task_id,base::get_new()->fd);
            $redis->EXPIRE($task_id,300);
        }else
        {
            return array('status'=>false,'msg'=>'data format error');
        }
    }
    function set_contract($data)
    {
        
    }
}