<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/3/18
 * Time: 下午7:34
 */
namespace Controller;
use Rbac\AdmintosourceModel;

class Hfapi extends \Yaf_Controller_Abstract
{
    // 后台用户编号
    protected $admin_id;
    // 后台客户组编号
    protected $client_id;
    // 需要跳过检查的Action
    protected $white_action = [];
    // 请求对象
    protected $_req = null;
    // session对象
    protected $_session = null;

    /**
     * 程序初始化
     */
    public function init()
    {
        // 获取request对象
        $this->_req = $this->getRequest();
        $this->_req = \Request::getInstance($this->_req);
        // session start
        $this->_session = \Yaf_Session::getInstance();
        // 定义全局常量
        $this->define_all();
        // 管理员是否登录判断
        //$this->is_login();
        // 关闭视图渲染
        \Yaf_Dispatcher::getInstance()->disableView();
       
        
        //if(CONTROLLER !='User' && ACTION !='code'){
        	//$this->checkAuth();
        //}
        
        	 
    }
    //检查微信授权
    protected function  checkAuth(){
    	$auth =$this->_req->getPost('auth',$this->getSession('auth_token'));
    	if(empty($auth)){
    		$this->ajaxReturn('304','请授权');
    	}
		
		$this->setSession('auth_token',$auth);
    	
    	$APP_ID='TAIDE_KFW001'; 
    	$APP_KEY='20170710101854-taide';
    	
    	$auth=\Encrypt::undes($auth, $APP_KEY);
    	$auth=json_decode($auth,true);
    	empty($auth) && $this->ajaxReturn('305','授权错误!');
    	$this->setSession('auth', $auth); //保存微信信息
    	if(time()-$auth['ctime']>604800 ){
    		$this->ajaxReturn('306','身份信息过期！');
    	}else{
    		return true;
    	}
    }
    protected function  birthday($birthday){
    	$age =$birthday;//strtotime($birthday);
    	if($age === false){return 0;}
    	list($y1,$m1,$d1) = explode("-",date("Y-m-d",$age));
    	$now =time();
    	list($y2,$m2,$d2) = explode("-",date("Y-m-d",$now));
    	$age = $y2 - $y1;
    	//echo  $y2 .'---'. $y1;
    	if((int)($m2.$d2) < (int)($m1.$d1))
    		$age -= 1;
    	return $age;
    }
    //获取用户ID
    protected function getUidByToken($token='')
    {
    	if(empty($token)){
    		$token =$this->_req->getPost('token',$this->getSession('token'));
    	}
    	//如果拿到了就去解码token,获取用户ID
    	if($token){
    		$uid	=\Wap\UserModel::getUseridByToken($token);
    		//将这个解码成功并且不是调试状态的token 存入到 Session 中
    		if(APP_DEBUG==false){
    			$this->setSession('token', $token);
    		}
    		return $uid;
    	}else{
    		$this->setSession('token', '');
    		return 0;
    	}
    }
	//获取用户ID
    protected function getUserByToken($token='')
    {		
    	return $this->getUidByToken($token);
    }
    //设置用户信息
    protected function getUserInfoByToken($token='')
    {
    	$userinfo=$this->getSession('user');
    	//$userinfo=false;
    	if($userinfo){
    		return $userinfo;
    	}else{
    		//通过token 获取用户ID
    		$uid=$this->getUidByToken($token);
    		if($uid){
    			//通过用户ID获取用户详细信息
    			$userinfo	=\Wap\UserModel::getUserInfoByid($uid);
    			//将用户信息保存起来，以备下次使用
    			$this->setSession('user',$userinfo);
    		}
    	}
    	return $userinfo;
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
   
    /**
     * successReturn/errorReturn,返回ajax请求
     *
     * @param string $info 信息
     * @param mixed $param 参数
     * @param string $url 跳转地址
     * @return json 字符串
     */
    protected function successReturn($info='', $param=[], $url='')
    {
        $this->ajaxReturn(true, $info, $param, $url);
    }
    protected function errorReturn($info='', $param=[], $url='')
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
    protected function ajaxReturn($status=false, $info='', $param=[], $url='', $encoding='utf-8')
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
    protected function define_all()
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
    protected function is_login()
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
    
}