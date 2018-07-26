<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/3/18
 * Time: 下午7:34
 */
namespace Controller;

class Allwx extends \Yaf_Controller_Abstract
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
    protected $auth = [];

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
        // 关闭视图渲染
        \Yaf_Dispatcher::getInstance()->disableView();
        $this->checkAuth();    
    }
    
    //检查微信授权
    protected function checkAuth(){
        // 若模块为Auth则不需要进行处理
        if (MODULE == 'Auth')
        {
            return true;
        }
        // 获取参数
        $param = $this->getParams([
            ['auth', 'require', '缺少用户身份信息Auth']
        ]);
        // 根据APPID查询，并缓存秘钥
        // 判断查询是否成
        // 使用秘钥进行Auth解码
        $auth = \Encrypt::undes($param['auth'], 'test');
        // 判断解码是否成功
        empty($auth) && $this->errorCode(false,10000, '请勿非法访问', 'decode error');
        // 进行json解码
        $auth = json_decode($auth, true);
        // 判断解码是否成功
        empty($auth) && $this->errorCode(false,10000, '请勿非法访问', 'json_decode error');
        // 进行全局赋值
        $this->auth = $auth;
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
     * @param string/array $param 参数
     * @param string $url 跳转地址
     * @return json 字符串
     */
    protected function successReturn($code='',$info='', $param=[], $url='')
    {
        $this->ajaxReturn(true, $code, $info, $param, $url);
    }

    protected function errorReturn($code='',$info='', $param=[], $url='')
    {
        $this->ajaxReturn(false, $code, $info, $param, $url);
    }

    /**
     * ajaxReturn,返回ajax请求
     * @param bool $status
     * @param string $info
     * @param array $param
     * @param string $url
     * @param string $encoding
     */
    protected function ajaxReturn($status=false, $code='', $info='', $param=[], $url='', $encoding = 'utf-8')
    {
        $temp = [];
        $temp['status'] = $status;
        $temp['code']   = $code;
        $temp['msg']    = $info;
        empty($param) || $temp['data'] = $param;
        empty($url)   || $temp['url']  = $url;
        header("Content-type: application/json;charset=$encoding");
        die(json_encode($temp));
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
            $this->ajaxReturn(false,101, $Validate->getError());
        }
        return $data;
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
	/**
     * 定义常量
     * @param string $key    缓存键值
     * @param mixed  $value  缓存数据
     * @param int $exprie 	缓存时间
     * @return true| false
     */
    protected function setCache($key,$value,$exprie=300){
    	return \Cache::set($key, $value,$exprie);
    }
    /**
     * 定义常量
     * @param string $key   	缓存键值
     * @param string $default 	缓存时间
     * @return object    原来存储是的是什么回来就是什么类型
     */
    protected function getCache($key,$default=''){
    	return \Cache::get($key,$default);
    }
    
}