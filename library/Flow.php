<?php

/**
 * 流量控制
 * @author ljw
 */
class Flow
{
	private  $redis;
	private static $obj; //被管控的ip
	protected $flow_max= 500;			//控制的最大流量 ,可以自己在redis 设置   kfw_max
	protected $flow_max_id=20; 			//被管控的ip最大访问量
	protected $USER_LOG='kfw_log'; 		//用户访问的日志被保存到这里 服务器日志,一天内失效
	protected $expiretime = 2;   		//以秒来计算
	protected $ipexpiretime = 40;
	protected $ipcount = 10;
	protected $ip_key = 'ip';

	/****
	* 获取一个单例
	*/
	public static  function getInstance($options = []){
		  $obj=new self($options);
		  return $obj;
	}
	/**
     * 构造函数
     * @param array $options 缓存参数
     * @access public
     */
    public function __construct($options = [])
    {
    	$this->redis	= new Myredis();  //缓存类
    	if($this->redis){
    		$max=$this->redis->get('kfw_max');  //可以在redis 中控制流量 
    		if($max )$this->flow_max =$max;
    	}
    }
	/**
	 * 流量控制
	 */
	public function newcheckFlow($key,$time,$module='')
	{
		$expiretime = $time-$this->expiretime;
		//判断总数量是否超过指定的流量
		$_len = intval($this->redis->zcount($key,$expiretime,$time));
		//超过设置的流量值，则不允许访问
		if( $_len >=$this->flow_max)
		{
			return true;
		}
		//删除过期的
		$this->redis->zRemrangebyscore($key,0,$expiretime);

		//增加对应的数量
	    $this->redis->zAdd($key,$time,$module);
		//存储日志
		$this->redis->lpush($this->USER_LOG, $_SERVER);
		//获取真实ip
		$ip = $this->getRealIp($_SERVER);
		$key = $this->ip_key.$ip;
		//redis获取ip访问量
		$temp_ip = $this->redis->get($key);
		if(empty($temp_ip))
		{

			$this->redis->set($key,1,$this->ipexpiretime);
		}
		elseif($temp_ip<$this->ipcount)
		{
			$this->redis->inc($key,1);
		}
        else
		{
			//需要被管控
			return true;
		}

		return false;
	}
	/**
	 * 获取真实ip地址
	 */
	function  getRealIp($temp)
	{
		$ip = $temp['REMOTE_ADDR'];
		if(isset($temp['HTTP_CDN_SRC_IP']))
		{
			$ip = $temp['HTTP_CDN_SRC_IP'];
		}
		elseif (isset($temp['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $temp['HTTP_CLIENT_IP']))
		{
			$ip = $temp['HTTP_CLIENT_IP'];
		}
		elseif(isset($temp['HTTP_X_FORWARDED_FOR']) &&
			preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $temp['HTTP_X_FORWARDED_FOR'], $matches))
		{
			foreach ($matches[0] as $xip)
			{
				if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip))
				{
					$ip = $xip;
					break;
				}
			}

		}

		return $ip;
	}
    /***
     * 检查目前流量是否受到限制
     */
    public function checkFlow($key){

    	$end=0;
    	$t1=[];
    	$_len=intval( $this->redis->get($key,0) ); //获取队列的长度
    	if( $_len > $this->flow_max){
    		return true;
    	}else{
    		//避免并发出现错误
    		$newExpire=$this->redis->getLock($key);
    		if($newExpire){
    			$this->redis->inc($key);
    			$this->redis->getLock($key,$newExpire);
    		}else{
    			return false;
    		}
    	}
    	$this->redis->lpush($this->USER_LOG, $_SERVER);
    	return false;
    }
    /***
    * 检查目前该ip是否受到限制
    * 如果有这个ip,并且ip 访问次数大于10次 禁止这个ip访问 知道缓存失效
    */
    public function checkIpFlow($ip){
    	if($this->redis){
    		$count=$this->redis->get($ip);			//检查这个键值
    		if($count && $count>$this->flow_max_id){
    			return true;
    		}
    	}else{
    		return false;
    	}
    } 
    /***
    * 刷新队列
    * 将检查队列出列，可以在每次成功返回和返回时调用一次
    */
    public function reflushList($key){
    	
    	$this->redis->dec($key);	
    	$count=$this->redis->get($key);
    	if($count<0){
    		$count=$this->redis->set(1);
    	}
    	return true;
    }
}
