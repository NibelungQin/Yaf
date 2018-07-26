<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/3/17
 * Time: 上午11:00
 */

ini_set('date.timezone', 'Asia/Shanghai');

// 定义是否开启debug
define('APP_DEBUG', true);
APP_DEBUG && ini_set('display_errors', true);
APP_DEBUG && error_reporting(E_ALL | E_STRICT);

//配置跨域支持,就是说这个域名下面的都可以调用
define('HTTP_ORIGIN','kfw001.com');

//定义 项目数据库配置文件,例如：api.php 对于正式数据库:api-db.ini  测试数据库:api-db-test.ini
define('APP_INI',str_replace('.php','',substr(__FILE__,strrpos(__FILE__,DIRECTORY_SEPARATOR)+1)));
//定义测试数据  --- 调试 时 使用        上线后 修改为false
define('TEST_INI',false); 
//定义项目路径
define('APP_PATH', realpath( dirname(__FILE__).'/../') );
if(TEST_INI){
    $App = new \Yaf_Application(APP_PATH.'/config_dev/'.APP_INI.'.ini', 'develop');
}else{
    $App = new \Yaf_Application(APP_PATH.'/config/'.APP_INI.'.ini', 'develop');
}
//启动项目
$App->bootstrap()->run();




