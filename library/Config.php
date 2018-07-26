<?php

/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/3/17
 * Time: 下午5:52
 */
class Config
{
	/**
	* 获取OSS配置
	* @param string $name
	* @param none
	* @return none
	*/
	public static function sms($name='sms')
	{
	    if(TEST_INI){
	        $config = new Yaf_Config_Ini(APP_PATH.'/config_dev/aliyun.ini', $name);
	    }else{
	        $config = new Yaf_Config_Ini(APP_PATH.'/config/aliyun.ini', $name);
	    }
		
		return $config->toArray();
	}
	/**
     * 获取OSS配置
     * @param string $name
     * @param none
     * @return none
     */
    public static function oss($name='master')
    {
        if(TEST_INI){
	        $config = new Yaf_Config_Ini(APP_PATH.'/config_dev/aliyun.ini', $name);
	    }else{
	        $config = new Yaf_Config_Ini(APP_PATH.'/config/aliyun.ini', $name);
	    }
        return $config->toArray();
    }
    /**
     * 获取数据库配置
     * @param string $name
     * @param none
     * @return none
     */
    public static function db($name='master')
    {
    	//判断数据库 add ljw
    	$ini_file_name='';
    	if(defined('APP_INI')) $ini_file_name=APP_INI.'-db';
    	if(empty($ini_file_name)) $ini_file_name='db';
    	
    	try{
    	    if(TEST_INI){
    	        $config = new Yaf_Config_Ini(APP_PATH.'/config_dev/'.$ini_file_name.'.ini',strtolower($name));
    	    }else{
    	        $config = new Yaf_Config_Ini(APP_PATH.'/config/'.$ini_file_name.'.ini',strtolower($name));
    	    }
    		
    	}
    	catch(Exception $e){
    		$config = new Yaf_Config_Ini(APP_PATH.'/config/'.$ini_file_name.'.ini', 'master');
    	}
    	return $config->toArray();
    }

    /**
     * 获取缓存配置
     * @param string $name
     * @param none
     * @return none
     */
    public static function cache($name='master')
    {
        if(TEST_INI){
            $config = new Yaf_Config_Ini(APP_PATH.'/config_dev/cache.ini', $name);
        }else{
            $config = new Yaf_Config_Ini(APP_PATH.'/config/aliyun.ini', $name);
        }
        
        
        return $config->toArray();
    }

    /**
     * 处理钩子
     * @param string $method
     * @param array $param
     * @return none
     */
    public static function __callStatic($method='', $param=[])
    {
        // 若调用get函数，则处理
        if ($method == 'get')
        {
            // 修改method
            $method = array_shift($param);
        }
        // 判断是否还有参数
        empty($param) && $param = ['master'];
        // 调用返回
        return self::$method(array_shift($param));
    }
}