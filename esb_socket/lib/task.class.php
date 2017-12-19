<?php
class task_class
{
	static protected $self;
	private function __construct($config)
	{
		$this->config = $config;
	}
	private function __sleep()
	{

	} 
	static public function get_new($config)
	{
		if(isset(self::$self)&&!emtpy(self::$self))
		{
			return self::$self;
		}
		self::$self = new self($config);
		return self::$self;
	}
 	function get_sql()
	{
		@$pd = $this->pdo->getAttribute(constant("PDO::ATTR_CONNECTION_STATUS"));
		if(!isset($pd)||empty($pd))
		{
			$this->pdo = new PDO('mysql:host=localhost;dbname='.$this->config->mysql152['dbname'].';port='.$this->config->mysql152['port'], $this->config->mysql152['user'], $this->config->mysql152['password'],array(PDO::ATTR_PERSISTENT => true));
        }
        return $this->pdo;
    }
    private function safe_check($id,$i=false)
    {
        if(!$i)
        {
            $i = 1;
        }
        if($i==5)
        {
            return false;
        }
        $pdo = $this->get_sql();
        $sql = $pdo->prepare("update balance set lock = 2 where lock = 1 and uid = ?");
        if($sql->execute(array($data['uid'])))
        {
            sleep(5);
            $i++;
            return $this->safe_check($id,$i);
        };
        return true;

    }


    function create_contract_buyer($data)
    {
        /*
        time:服务器时间
        type:1买入，2卖出
        price:money
        uid:用户id
        pid:交易产品
        id:订单id
        */
        if(!$this->safe_check($data['data']['id']))
        {
            $data['data'] = array('method'=>'normal','data'=>array('status'=>false,'msg'=>'balance auth false'));
            return $data;
        }

        $sql = $pdo->prepare("select balance,freeze,`status`,`key` from balance where `status` = '1' and uid =? and `key` = md5(balance)");
        $sql->execute(array($data['data']['id']));
        $b_data = $sql->fetch();
        if(!isset($b_data['balance']))
        {
            $data['data'] = array('method'=>'normal','data'=>array('status'=>false,'msg'=>'balance auth false'));
            return $data;
        }
        $sql = $pdo->prepare("select last(key) from money_flow where uid = ?");
        $sql->execute(array($data['id']));
        $f_key = $sql->fetch();
        if(isset($f_key))
        {
            $str = $b_data['freeze'].$b_data['balance'].$b_data['key'].$b_data['status'];
            if(md5($str)!==$f_key)
            {
                $this->lock_balance();
                $data['data'] = array('method'=>'normal','data'=>array('status'=>false,'msg'=>'balance auth false'));
                return $data;
            }
        }
        $sql = $pdo->prepare("update balance set banlance = banlance - ?,freeze = freeze + ?,`key` = md5(balance) where uid = ? and balance <= ?");
        $count_money = $data['data']['num']*$data['data']['price'];
        if(!$sql->execute(array($count_money,$count_money,$data['id'],$count_money)))
        {
            $data['data'] = array('method'=>'normal','data'=>array('status'=>false,'msg'=>'balance auth false'));
            return $data;
        }
        $sql = $pdo->prepare("insert into money_flow(uid,price,type,direction,time,key) select ?,?,13,2,?,md5(balance+freeze) from balance where uid = ?");
        if(!$sql->execute(array($data['id'],$count_money,time())))
        {
            $this->lock_balance($data['id']);
            $data['data'] = array('method'=>'normal','data'=>array('status'=>false,'msg'=>'money_flow_false'));
            return $data;
        }
        $m_id = $pdo->lastInsertId();
        $sql = $pdo->prepare("insert into contract(uid,mid,m_key,sum_price,unit_price,count,time,type,status) select ?,?,md5(key),?,?,?,?,1,13 from money_flow where id = ?");
        if(!$sql->execute(array($data['data']['id'],$m_id,$count_money,$data['data']['price'],$data['data']['num'],time(),$m_id)))
        {
            $this->log('contract create fail',2,'money_flow_id:'.$m_id);
            $data['data'] = array('method'=>'normal','data'=>array('status'=>false,'msg'=>'contract_create_fail'));
            return $data;
        }
        $t_id = $pdo->lastInsertId();
        $redis = base::get_new()->get_redis();
        $redis->select(2);
        $redis->hmset('w_'.$t_id,array('price'=>$data['data']['price'],'num'=>$data['data']['num'],'direction'=>'buy'));
        $data['data'] = array('method'=>'transaction','data'=>array('method'=>'put','data'=>array('id'=>$t_id)));
        return $data;
    }


    function create_contract_seller($data)
    {
        if(!$this->safe_check($data['data']['id']))
        {
            $data['data'] = array('method'=>'normal','data'=>array('status'=>false,'msg'=>'balance auth false'));
            return $data;
        }
        if(!$this->check_positions($data['data']['pid'],$data['data']['num']))
        {
            $data['data'] = array('method'=>'normal','data'=>array('status'=>false,'msg'=>'invalid information'));
            return $data;
        }

    }

    function check_positions($pid,$num)
    {
        $sql = $pdo->prepare("update positions set `status` = 3 where pid = ? and now_num <= ? and `status` = 1");
        if(!$sql->execute(array($pid,$num)))
        {
            return false;
        }
        return true;
    }

    function lock_balance($id)
    {
        $sql = $pdo->prepare("update balance set `status` = '2' where uid = ?");
        $sql->execute(array($id));
        $this->sql_log('funds error',3,'user_id:'.$id);
    }

    function sql_log($title='',$type=0,$msg='',$note='')
    {
        $sql = $pdo->prepare("insert into log(title,type,msg,note) values (?,?,?,?)");
        $sql->execute(array($title,$type,$msg,$note));
    }

}