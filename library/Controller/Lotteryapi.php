<?php
/**
 * Created by PhpStorm.
 * User: ljw
 * Date: 2017/11/28
 * Time: 下午7:34
 */
namespace Controller;
use Rbac\AdmintosourceModel;

class Lotteryapi extends \Yaf_Controller_Abstract
{
    //后台用户编号
    protected $admin_id;
    // 后台客户组编号
    protected $client_id;
    // 需要跳过检查的Action
    protected $white_action = [];
    // 请求对象
    protected $_req = null;
    // session对象
    protected $_session = null;
    
    protected $_jwt = null;
    protected $_cookie_key ='kfwjwtlotter';
    
    //cookie 设置
    protected $cookie=[
	    	'expire'	=>5,
	    	'path'		=>"/",
	    	'secure'	=>false,
	    	'domaim'	=>'kfw001.com',
	    	'httponly'	=>true
    	];
    protected $aid =0; 		//当前活动的id
    protected $info =[]; 	//活动信息
    protected $user =[]; 	//当前活动参与用户的信息
    
    /**
     * 程序初始化
     */
    public function init()
    {
        
        $this->_req = $this->getRequest();
        $this->_req = \Request::getInstance($this->_req); //获取request对象
        $this->_session = \Yaf_Session::getInstance();
        $this->define_all();
        \Yaf_Dispatcher::getInstance()->disableView();
        
        $this->checkToken();
    }
    /***
     * 在每次控制器方法调用 完成后需要执行
     */
    public function setNewToken($param,$expire=0){
    	$token=\Jwt::getInstance()->generateToken($param);
    	if($expire){
    		$t=setcookie($this->_cookie_key,$token,$this->cookie['expire'],$this->cookie['path'],$this->cookie['domaim'],$this->cookie['secure'],$this->cookie['httponly']);
    	}else{
    		$t=setcookie($this->_cookie_key,$token,$expire,$this->cookie['path'],$this->cookie['domaim'],$this->cookie['secure'],$this->cookie['httponly']);
    	}
    	if(empty($t)){
    		return $token;
    	}else{
    		return false;
    	}
    }
    /***
    * 抽奖游戏必须带上 token 否则不允许 访问
    */
    private function checkToken(){
    	
    	#1 从cookie 中读取 
    	$token=isset($_COOKIE[$this->_cookie_key]) ? $_COOKIE[$this->_cookie_key] :'';
    	#1.1  如果cookie 中没有 尝试 读取传输值
    	if(empty($token)) { $token=$this->_req->get('jwttoken'); }
    	
    	if($token){
    		#1.2  对$token 解码
    		$claims=\Jwt::getInstance()->getVerifiedClaims($token);
    		if($claims){
    			$this->info	=isset($claims['param']['info'])? $claims['param']['info'] :[];
    			$this->aid	=isset($claims['param']['info']['aid'])? $claims['param']['info']['aid']:0;
    			$this->uid	=isset($claims['param']['user']['uid'])? $claims['param']['user']['uid']:0;
    			return true;
    		}
    	}
    	
    	$this->info=[]; //活动相关
    	$this->aid=0;	//活动id
    	$this->uid=0;   //参与活动用户日志id
    	
    	return false; 
    }
    /*
    * 用户授权
    */
    protected function auth()
    {
    	$auth=$this->_req->get('auth');
    	if(empty($auth)){
    		$this->ajaxReturn('304','请授权');
    	}
    	//定死了： 还是走统一授权 
    	$APP_ID='Lottery_KFW001';
    	$APP_KEY='20170710101854-Lottery';
    	
    	$auth=\Encrypt::undes($auth, $APP_KEY);
    	$auth=json_decode($auth,true);
    	empty($auth) && $this->ajaxReturn('305','授权错误!');
    	
    	$this->setSession('auth', $auth); //保存微信信息
    	if(time()-$auth['ctime']>604800 ){
    		$this->ajaxReturn('306','身份信息过期！');
    	}else{
    		//包含uid (微信相关数据储存),openid,ctime 授权过期时间 
    		$this->user=$auth;
    		return $auth;
    	}
    }
    /**
     * successReturn/errorReturn,返回ajax请求
     *
     * @param string $info 信息
     * @param mixed $param 参数
     * @param string $url 跳转地址
     * @return json 字符串
     */
    public function successReturn($info='', $param=[], $url='')
    {
        $this->ajaxReturn(true, $info, $param, $url);
    }
    public function errorReturn($info='', $param=[], $url='')
    {
        $this->ajaxReturn(false, $info, $param, $url);
    }
    protected function errorCode($code=0, $info='', $param=[], $url='')
    {
        $data = [
            'info' => $info,
            'code' => $code,
        ];
        empty($param) || $data['param'] = $param;
        empty($url) || $data['url'] = $url;
        $this->ajaxReturn(false, $data);
    }
    /**
     * ajaxReturn,返回ajax请求
     * @param bool $status
     * @param string $info
     * @param array $param
     * @param string $url
     * @param string $encoding
     */
    public function ajaxReturn($status=false, $info='', $param=[], $url='', $encoding='utf-8')
    {
        // 判断是否为数组
        if (is_array($info))
        {
            $data = $info;
            $data['status'] = $status;
        }
        else
        {
            $data = [
                'status' => $status,
                'info' => $info,
            ];
            empty($param) || $data['param'] = $param;
            empty($url) || $data['url'] = $url;
        }
        header("Content-type: application/json;charset=$encoding");
        die(json_encode($data));
    }

    /**
     * 预定义一些全局常量
     * @param null
     * @return null
     */
    private function define_all()
    {
        define('MODULE', $this->_req->getModuleName());
        define('CONTROLLER', $this->_req->getControllerName());
        define('ACTION', $this->_req->getActionName());
    }

    /**
     * 管理员是否登录判定
     * @param none
     * @return bool
     */
    private function is_login()
    {
        // 若是Admin模块，公共可访问
        if (in_array(ACTION, $this->white_action))
        {
            return true;
        }
        // 获取登录状态
        $token = $this->_req->get('token');//isset($_POST['token'])?$_POST['token']:'';
        // 判断是否已登录
        if (empty($token))
        {
            if (!(MODULE == 'System'
                && CONTROLLER == 'Login'))
            {
                $this->errorCode(10000, '用户还未登录，请登录');
            }
        }
        else
        {
            // 解密
            $admin = \Encrypt::undes($token, 'KFW_001');
            $admin = json_decode($admin, true);
            // 获取用户信息
            empty($admin) && $this->errorCode(10000, '缺少用户信息');
            // 判断用户是否失效
            time() - $admin['ctime'] < 3600 || $this->errorCode(10000, '用户身份已失效，请重新登录');
            // 用户编号
            $this->admin_id = $admin['id'];
            $this->client_id = $admin['client_id'];
            // 权限管理判断
            $this->check_purview($admin);
        }
    }
    /**
     * 判断管理员是否有模块查看的权限
     * @param array $admin
     * @return html / bool
     */
    private function check_purview(Array $admin=[])
    {
        // 超级管理员,跳过权限检查
        if ($admin['level'] < 2)
        {
            return true;
        }
        // 检查权限
        $param = [
            'admin_id' => $this->admin_id,
            'module' => MODULE,
            'controller' => CONTROLLER,
            'action' => ACTION,
            'role_status' => 1,
            'source_status' => 1,
        ];
        $count = AdmintosourceModel::where($param)->count(1);
        // 若存在，则完成检查
        if (!empty($count))
        {
            return true;
        }
        // 返回用户
        $this->errorCode(10001, '您没有权限访问');
    }

    /**
     * 获取参数,并验证参数情况
     * @param [array] $data 获取参数规则
     * @param none
     * @return bool
     */
    protected function getParams(Array $rule=[])
    {
        // 取出规则第一列,获取字段值(测试阶段,暂时使用get,上线改为getPost)
        $data = $this->_req->get(array_column($rule, 0));
        // 建立验证规则,并进行数据检验
        $Validate = \Validate::make();
        $result = $Validate->check($data, $rule);
        // 判断是否符合规范
        if ($result == false)
        {
            $this->ajaxReturn(101, $Validate->getError());
        }
        return $data;
    }

    /**
     * successReturn/errorReturn,返回ajax请求
     *
     * @param string $info 信息
     * @param string/array $param 参数
     * @param string $code 状态码
     * @return json 字符串
     */
    public function success($info='', $param=[], $url='')
    {
        $this->tips(true, $info, $param, $url);
    }

    public function error($info='', $param=[], $url='')
    {
        $this->tips(false, $info, $param, $url);
    }

    public function tips($status, $info)
    {
        throw new \Exception($info);
    }
    
    //是微信登录
    public function is_weixin()
    {
    	if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
			return true;
		}	
		return false;
    }
    
    
    public function getip(){
    	if(getenv('HTTP_CLIENT_IP')){
    		$onlineip = getenv('HTTP_CLIENT_IP');
    	}
    	elseif(getenv('HTTP_X_FORWARDED_FOR')){
    		$onlineip = getenv('HTTP_X_FORWARDED_FOR');
    	}
    	elseif(getenv('REMOTE_ADDR')){
    		$onlineip = getenv('REMOTE_ADDR');
    	}
    	else{
    		$onlineip = $HTTP_SERVER_VARS['REMOTE_ADDR'];
    	}
    	
    	return $onlineip;
    }
    
    //获取用户信息到session,如果是调试 那么 $key=token,就放回固定的  token
    protected function getSession($key)
    {
    	if(isset($_SESSION[$key])){
    
    		$info=$this->_session->get($key);
    		if($info){
    			return json_decode($info,true);
    		}
    	}
    	//测试情况 下 就用这个
    	if(APP_DEBUG && $key=='token' ){ return 'j5vq0B0KKhM*cAXFPrXAdw__';}
    
    	return '';
    }
    //设置用户信息到session
    protected function setSession($key,$data)
    {
    	if($data){
    		$this->_session->set($key, json_encode($data));
    	}
    }
}