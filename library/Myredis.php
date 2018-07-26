<?php

/**
 * Class Cache_Driver_Redis
 * @author none
 */
class Myredis
{
	private  $handler;
	private  $timeout=3; //锁的默认失效时间
    protected $options = [
        'host'       => '192.168.220.253',
        'port'       => 6379,
        'password'   => 'kfwljw0912',
        'select'     => 0,
        'timeout'    => 5,
        'expire'     => 86400, //秒 24 小时
        'persistent' => true,
        'prefix'     => 'w',   // w_kfw_red  w_kfw_max
    ];
	private  $tag = '';
    /**
     * 构造函数
     * @param array $options 缓存参数
     * @access public
     */
    public function __construct($options = [])
    {


        if (!extension_loaded('redis')) {
            die('redis');
        }
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        $func          = $this->options['persistent'] ? 'pconnect' : 'connect';
        $this->handler = new \Redis;
        $this->handler->$func($this->options['host'], $this->options['port'], $this->options['timeout']);

        if ('' != $this->options['password']) {
            $this->handler->auth($this->options['password']);
        }
		//选择哪个库
        if (0 != $this->options['select']) {
            $this->handler->select($this->options['select']);
        }


		
    }
    /**
     * 判断缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public function has($name)
    {
        return $this->handler->get($this->getCacheKey($name)) ? true : false;
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function get($name, $default = false)
    {
        $value = $this->handler->get($this->getCacheKey($name));
        if (is_null($value)) {
            return $default;
        }
        $jsonData = json_decode($value, true);
        // 检测是否为JSON数据 true 返回JSON解析数组, false返回源数据 byron sampson<xiaobo.sun@qq.com>
        return (null === $jsonData) ? $value : $jsonData;
    }

    /**
     * 写入缓存
     * @access public
     * @param string    $name 缓存变量名
     * @param mixed     $value  存储数据
     * @param integer   $expire  有效时间（秒）
     * @return boolean
     */
    public function set($name, $value, $expire = null)
    {
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        if ($this->tag && !$this->has($name)) {
            $first = true;
        }
        $key = $this->getCacheKey($name);
        //对数组/对象数据进行缓存处理，保证数据完整性  byron sampson<xiaobo.sun@qq.com>
        $value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
        if (is_int($expire) && $expire) {
            $result = $this->handler->setex($key, $expire, $value);
        } else {
            $result = $this->handler->set($key, $value);
        }
        isset($first) && $this->setTagItem($key);
        return $result;
    }

    /**
     * 自增缓存（针对数值缓存）
     * @access public
     * @param string    $name 缓存变量名
     * @param int       $step 步长
     * @return false|int
     */
    public function inc($name, $step = 1)
    {
    	
        $key = $this->getCacheKey($name);
    
       return $this->handler->incrby($key, $step);
        
         
    }
    /**
    * 有序集合,数量判断
    */
    public function  zcount($name, $start_time, $end_time)
    {
        $key = $this->getCacheKey($name);
        return $this->handler->zCount($key,$start_time,$end_time);
    }
    /**
     * 有序集合，健值对的添加
     */

    public function zadd($name, $time, $value, $expire = null)
    {
        if (is_null($expire)) {
            //默认保存1天时间
            $expire = $this->options['expire'];
        }
        $key = $this->getCacheKey($name);

        if( $expire &&  $this->handler->exists($key)==0){
            $result = $this->handler->zAdd($key,$time,$value);//保存
            $this->handler->expire($key,$expire);		   //设置过期时间
        }else{
            $result = $this->handler->zAdd($key,$time,$value);
        }

        return $result;
    }
    /**
     * 有序集合，删除某个健，指定范围的值
     */
    public function  zRemrangebyscore($name, $start_time, $end_time)
    {
        $key = $this->getCacheKey($name);
        return $this->handler->zRemRangeByScore($key,$start_time,$end_time);
    }
    /**
     * 有序集合，获取制定范围
     */
    public function  zIncrlist($name, $step = 1,$value )
    {
        $key = $this->getCacheKey($name);
        return $this->handler->zIncrBy($key,$step,$value);
    }
    /**
     * 有序集合，获取制定范围
     */
    public function  zRangebyscore($name, $start = 0, $end = 1000000,$option = ['withscores' => TRUE])
    {
        $key = $this->getCacheKey($name);
        return $this->handler->zRangeByScore($key,$start,$end,$option);
    }
    /**
     * 有序集合，获取制定范围
     */
    public function  zRangebylist($name, $start = 0, $end = -1,$option = TRUE)
    {
        $key = $this->getCacheKey($name);
        return $this->handler->zRevRange($key,$start,$end,$option);
    }
    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param string    $name 缓存变量名
     * @param int       $step 步长
     * @return false|int
     */
    public function dec($name, $step = 1)
    {
        $key = $this->getCacheKey($name);
        return $this->handler->decrby($key, $step);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function rm($name)
    {
        return $this->handler->delete($this->getCacheKey($name));
    }
    /**
     * 清除缓存
     * @access public
     * @param string $tag 标签名
     * @return boolean
     */
    public function clear($tag = null)
    {
        if ($tag) {
            // 指定标签清除
            $keys = $this->getTagItem($tag);
            foreach ($keys as $key) {
                $this->handler->delete($key);
            }
            $this->rm('tag_' . md5($tag));
            return true;
        }
        return $this->handler->flushDB();
    }
    
    
    public function getCacheKey($key){
    	
    	if(isset($this->options['prefix'])){
    		return $this->options['prefix']."_".$key;
    	}else{
    		return $key;
    	}
    }
    /**
     * 获取队列的长度
     * @access public
     * @param string $tag 标签名
     * @return boolean
     */
    public function llen($key)
    {
		
    	if ($key) {
    		return $this->handler->llen($this->getCacheKey($key));
    	}else{
    		return 0;
    	}
    }
    /**
     * 写入到队列中
     * @access public
     * @param string $tag 标签名
     * @return boolean
     */
    public function lpush($name, $value, $expire = null)
    {
    	if (is_null($expire)) {
    		//默认保存1天时间
    		$expire = $this->options['expire'];
    	}
    	$key = $this->getCacheKey($name);
    	$value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
    	
    	if( $expire &&  $this->handler->exists($key)==0){
    		$result = $this->handler->lpush($key, $value); //保存
    		$this->handler->expire($key,$expire);		   //设置过期时间	
    	}else{
			$result = $this->handler->lpush($key, $value);
    	}
    	return $result;
    }
    /**
     * 移除并获取列表最后一个元素
     * @access public
     * @param string $tag 标签名
     * @return boolean
     */
    public function rpop($name)
    {
    	$value=$this->handler->rpop($this->getCacheKey($name));
    	$jsonData = json_decode($value, true);
    	return (null === $jsonData) ? $value : $jsonData;
    }
    /**
     * 移出并获取列表的第一个元素
     * @access public
     * @param string $tag 标签名
     * @return boolean
     */
    public function lpop($name)
    {
    	$value=$this->handler->lpop($this->getCacheKey($name));
    	$jsonData = json_decode($value, true);
    	return (null === $jsonData) ? $value : $jsonData;
    	 
    }
    /**
     * 移出并获取列表的第一个元素
     * @access public
     * @param string $tag 标签名
     * @return boolean
     */
    public function lindex($name,$index=0)
    {
    	$value=$this->handler->lindex($this->getCacheKey($name),$index);
    	$jsonData = json_decode($value, true);
    	return (null === $jsonData) ? $value : $jsonData;
    }
    /**
     * @desc 获取锁
     *
     * @param key string | 要上锁的键名
     * @param timeout int | 上锁时间
     */
    public function getLock($key, $timeout = 2)
    {
    	$timeout = $timeout ? $timeout : $this->timeout;
    	$lockCacheKey = $this->getLockCacheKey($key);
    	$expireAt = time() + $timeout;
    	//如果已经存在这个键值 返回fals 
    	$isGet = (bool)$this->handler->setnx($lockCacheKey, $expireAt);
    	if ($isGet) {
    		return $expireAt;
    	}
    	$count=1000; //默认尝试100次,我也不知到是否合理，需啊哟实际调试
    	while ($count) {
    		$count--;
    		$time = time();
    		$oldExpire = $this->handler->get($lockCacheKey);
    		if ($oldExpire >= $time) {
    			continue;
    		}
    		$newExpire = $time + $timeout;
    		$expireAt = $this->handler->getset($lockCacheKey, $newExpire);
    		if ($oldExpire != $expireAt) {
    			continue;
    		}
    		$isGet = $newExpire;
    		break;
    	}
    	return $isGet;
    }
    /**
     * 获取所有的健值对
     */
    public function getallkeys($name)
    {

        $pattern = $this->getCacheKey($name);
        return $this->handler->keys($pattern);
    }
    /**
     * @desc 释放锁
     *
     * @param key string | 加锁的字段
     * @param newExpire int | 加锁的截止时间
     *
     * @return bool | 是否释放成功
     */
    public function releaseLock($key, $newExpire)
    {
    	$lockCacheKey = $this->getLockCacheKey($key);
    	if ($newExpire >= time()) {
    		return $this->handler->del($lockCacheKey);
    	}
    	return true;
    }
    /**
     * @desc 获取锁键名
     */
    public function getLockCacheKey($key)
    {
    	return "kfw_lock_{$key}";
    }

    /**
     * 获取列表,返回所有的列表
     */
    public function getList($name="", $start=0, $end=-1)
    {
        //获取对应的健
        $key = $this->getCacheKey($name);
        //获取list数据
        $list = $this->handler->lRange($key,$start,$end);
        //json_decode转译
        foreach($list as $k=>&$v)
        {
            $temp = json_decode($v, true);
            (null !== $temp) && $v = $temp;
        }
        //返回值
        return $list;
    }
}
