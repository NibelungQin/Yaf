<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/3/17
 * Time: 下午4:51
 */
class Bootstrap extends Yaf_Bootstrap_Abstract
{
	
	
	public function _initConfig(Yaf_Dispatcher $dispatcher)
	{
		
	}
	
	
	
    /**
     * 初始化session
     * @param Yaf_Dispatcher $dispatcher
     */
    public function _initSession(Yaf_Dispatcher $dispatcher)
    {
    	$session=[];
    	$config = Yaf_Application::app()->getConfig();
    	if (!empty($config->session))
    	{
    			$session = $config->session;
    			session_set_cookie_params(
    			$session->cookie_lifetime,
    			$session->cookie_path,
    			$session->cookie_domain,
    			$session->cookie_secure,
    			$session->cookie_httponly
    		);
    		//session_name($session->name);
    		
    		//print_r($config);
    		
    	}
    	
    	
    	Yaf_Session::getInstance()->start();
    	
    	$this->setHeader();
    	
    	//header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
    	//setcookie("kfwapi", $sid, time()+3600, "/", $config->session->cookie_domain);
    	
    	//echo $sid;
       
        
  
    }

    /**
     * 初始化路由一个路由
     * @param Yaf_Dispatcher $dispatcher
     */
    public function _initRoute(Yaf_Dispatcher $dispatcher)
    {
        $router = $dispatcher->getRouter();

        $center_pid = new \Yaf_Route_Regex(
            '#center/([0-9]+)#', [
                'module' => 'admin',
                'controller' => 'index',
                'action' => 'index',
            ],[1=>'pid']
        );
        $admin_route =  new \Yaf_Route_Rewrite(
            'center', [
                'module' => 'admin',
                'controller' => 'index',
                'action' => 'index',
            ]
        );
        $router->addRoute('center', $admin_route);
        $router->addRoute('center_pid', $center_pid);
    }

    /**
     * 初始化返回值，主要处理跨域请求
     * @param Yaf_Dispatcher $dispatcher
     */
    public function _initResponse(Yaf_Dispatcher $dispatcher)
    {
        $this->setHeader();
    }
    
    /***
     *  setHeader
    */
    private function setHeader(){
    	// 获取当前请求所在域名
    	$origin = isset($_SERVER['HTTP_ORIGIN'])? $_SERVER['HTTP_ORIGIN'] : '';
    	// 允许域名
    	$allow_origin =[
            'http://h.kfw001.com:8080',
            'h.kfw001.com:8080',
			'http://game.kfw001.com',
        ];
    	// 判断是否来自快房网, 判断当前域名是否在允许目录
    	if (strpos($origin, HTTP_ORIGIN) !== false || in_array($origin, $allow_origin))
    	{
    		header('Access-Control-Allow-Origin:'.$origin);
    		header('Access-Control-Allow-Credentials: true');
    		header('Access-Control-Allow-Headers:X_Requested_With,content-type,X-Requested-With');
    		header('Access-Control-Allow-Methods: GET, POST,PUT,DELETE');
    
    	}
    }
}