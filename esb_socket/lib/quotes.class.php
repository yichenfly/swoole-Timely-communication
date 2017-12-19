<?php
class quotes_class
{
    static protected $self;
    private function __construct()
    {
    }
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
            case 'get_all_price':
                return base::json($this->get_all_price($data));
            break;
            case 'get_id_price':
                return base::json($this->get_id_price($data));
            break;
            case 'set_price':
                return base::json($this->set_price($data));
            break;
        }
    }
    function get_all_price($data)
    {
        $redis = base::get_new()->get_redis();
        $redis->select(2);
        $list = $redis->SMEMBERS('quote_list');
        $price = $redis->mget(array_map(function($e){
            return 'q_p_'.$e;
        },$list));
        $arr_data = array();
        foreach($list as $key=>$val)
        {
            $arr_data[$key] = $redis->hgetall('q_'.$val);
            $arr_data[$key]['price'] = $price[$key];
            $arr_data[$key]['id'] = $val;
        }
        $data['data'] = $arr_data;
        return $data;
    }
    function get_id_price($data=false)
    {
        if(!isset($data['id']))
        {
            return false;
        }
        $redis = base::get_new()->get_redis();
        $redis->select(2);
        $data['data'] = $redis->SMEMBERS('q_p_ti_'.$data['id']);
        // 现价集合推入  q_p_ti_id
        return $data;
    }

    function set_price($data)
    {

    }
    
    function test_data()
    {
        $redis = base::get_new()->get_redis();
        $redis->select(2);
        // title 标题 max 最大 p 价格 c 总  t 当天 o 开盘  cl 停盘 ti  time

        // 集中信息存储
        $redis->hmset('q_123456',array('title'=>'蝙蝠侠','max_p'=>'123456','min_p'=>'1245','c_n'=>'1324658312','t_c_n'=>'134651223','t_c_p'=>'123121','o_p'=>'12345','cl_p'=>'0'));
        $redis->hmset('q_123457',array('title'=>'蝙蝠侠','max_p'=>'123456','min_p'=>'1245','c_n'=>'1324658312','t_c_n'=>'134651223','t_c_p'=>'123121','o_p'=>'12345','cl_p'=>'0'));


        // 实时报价
        $redis->set('q_p_123457','457');
        $redis->set('q_p_123456','456');

        // 注册行情
        $redis->SADD('quote_list','123456');
        $redis->SADD('quote_list','123457');

        // 分时行情更新
        $redis->SADD('q_p_ti_123456',time().'::123456');
    }
}