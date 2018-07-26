<?php
/**
 * Created by PhpStorm.
 * User: Marico
 * Date: 2017/1/17
 * Time: 09:23
 */
namespace  Controller;

class Voteapi extends \Yaf_Controller_Abstract
{
    // 会员编号
    protected $member_id;
    // 当前公众号编号
    protected $public_id;
    // 当前项目编号
    protected $project_id;
    protected $appid = 'wx93250682fd7902eb';
    // secret
    protected $appSecret = '74ec7e8c8d6f318dfccb8868b4b2814c';
    protected $user_id = 0;

    protected $openid;
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
    }
    
    /*
    *判断token
    */
    public function  checktoken()
    {
        $token = $this->_req->get('token');
        //判断
        if(empty($token)) $this->codeReturn(888,"token无效");
        //解密
        $token = \Encrypt::undes($token);
        $token===false&&$this->codeReturn(888,"token无效");
        //转成数组
        $token = json_decode($token,true);
        //auth时间是否过期,7天后过期，token
        (time()-$token['ctime']>7*24*3600)&&$this->codeReturn(333,"token过期");
        //返回解密的token
        return $token;
    }
    /**
     * successReturn/errorReturn,返回ajax请求
     *
     * @param string $info 信息
     * @param string/array $param 参数
     * @param string $code 状态码
     * @return json 字符串
     */
    public function successReturn($info='', $param=[], $url='')
    {
        $this->ajaxReturn(200, $info, $param, $url);
    }

    public function errorReturn($info='', $param=[], $url='')
    {
        $this->ajaxReturn(500, $info, $param, $url);
    }

    public function codeReturn($code=404, $info='', $param=[], $url='')
    {
        $this->ajaxReturn($code, $info, $param, $url);
    }
    public function codeReturn1($status=false, $info='', $code=404)
    {
        $this->ajaxReturn1($status, $info, $code);
    } /**
 * ajaxReturn,返回ajax请求(支持跨域)
 * @param bool $status
 * @param string $info
 * @param array $param
 * @param string $url
 * @param string $encoding
 */
    public function ajaxReturn1($status=false, $info='', $code=404, $encoding='utf-8')
    {
        // 打印回复格式
        header("Content-type: application/json;charset=$encoding");
        // 准备数据
        $data = [
            'status' => $status,
            'info' => $info,
        ];

        empty($code) || $data['code'] = $code;
        // 打成json格式数据
        $data = json_encode($data);
        // 判断是否为跨域
        $callBack = $this->_req->get('callback');
        empty($callBack) || header("Access-Control-Allow-Origin: *");
        empty($callBack) || $data = $callBack.'('.$data.')';

        die($data);
    }
    /**
     * ajaxReturn,返回ajax请求(支持跨域)
     * @param bool $status
     * @param string $info
     * @param array $param
     * @param string $url
     * @param string $encoding
     */
    public function ajaxReturn($status=false, $info='', $param=[], $url='', $encoding='utf-8')
    {
        // 打印回复格式
        header("Content-type: application/json;charset=$encoding");
        // 准备数据
        $data = [
            'status' => $status,
            'info' => $info,
        ];

        empty($param) || $data['param'] = $param;
        empty($url) || $data['url'] = $url;
        // 打成json格式数据
        $data = json_encode($data);
        // 判断是否为跨域
        $callBack = $this->_req->get('callback');
        empty($callBack) || header("Access-Control-Allow-Origin: *");
        empty($callBack) || $data = $callBack.'('.$data.')';

        die($data);
    }
    /**
     *微信公众号授权
     */
    protected function _weixinofficalgrant()
    {

        $this->appid = "test";

        $info = $this->getOpenid();
        return $info;
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
        $this->member_id = $this->_session->get('is_member');
    }
    /**
     * 网页授权处理页,进行跳转
     * @param
     * @return
     */
    protected function getOpenid()
    {
        $info = $this->_session->get('_user_info_');
        $user_id = $this->_session->get('_user_id');
        //session
        $REQUEST_URI = $this->_session->get('_REQUEST_URI');
        if(empty($REQUEST_URI))
        {
            // 记录授权前访问地址
            $REQUEST_URI = $_SERVER['REQUEST_URI'];
            // 记录授权前访问地址
            $this->_session->set('_REQUEST_URI', $REQUEST_URI);
        }
        // 若seesion中存在,则不需要再次授权
        if(!empty($info) && !empty($user_id))
        {
            $this->user_id = $user_id;
            return $info;
        }
        // 授权回调地址,用于接收时效性code
        $callback = \Url::to(
            'Vote/Auth/auth',
            '',
            true
        );
        $callback = $callback."/?code={code}";
        $callback = urlencode($callback);
        $forward = 'http://api.mp.kfw001.com/auth/wechat/web?'
            ."appid={$this->appid}&redirect_uri={$callback}&response_type=code"
            ."&scope=snsapi_userinfo";
        header('Location: ' . $forward);
        exit;
    }
    /**
     * 执行成功提示界面
     * @param
     * @return
     */
    protected function success($message='操作成功')
    {
        $data = [
            'title' => '操作成功',
            'icon' => 'weui_icon_success',
            'message' => $message,
        ];
        $this->tips($data);
    }

    /**
     * 错误
     */
    public function error($message='操作失败')
    {
        $data = [
            'title' => '操作失败',
            'icon' => 'weui_icon_warn',
            'message' => $message,
        ];
        extract($data,EXTR_OVERWRITE);

        $template = APP_PATH.'/api/views/index/tips.html';
        include $template;
        exit;
    }

    /**
     * @param 提示
     */
    public function tips($data)
    {

        $template = APP_PATH.'/api/views/index/tips.html';

        $this->_view->display($template, $data);

        exit();
    }

    /**用于统计用户行为数据
     * @param $info
     */
    protected function user_log($info)
    {
        if(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])=="xmlhttprequest"){
            $type = 3;
        }
        else if($_SERVER['REQUEST_METHOD']=="POST")
        {
            $type = 2;
        }
        else if($_SERVER['REQUEST_METHOD']=="GET")
        {
            $type = 1;
        }

        $data = [
            'from' => $_SERVER['HTTP_HOST'],
            'url' => $_SERVER['REQUEST_URI'],
            'modules' => $this->_req->getModuleName(),
            'controller' => $this->_req->getControllerName(),
            'action' => $this->_req->getActionName(),
            'http' => $type,
            'gpc' => json_encode(array_merge($_POST, $_GET))
        ];

        if (is_string($info))
        {
            $data['openid'] = $info;
        }
        else
        {
            $data = array_merge($data, $info);
        }

        // 创建客户端对象
        $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC); //同步阻塞

        // 发起连接
        $client->connect(
            '10.45.185.152',
            10001,
            0.5,
            0
        );

        // 提交数据(仅支持字符串提交)
        $client->send(json_encode($data));

        // 接收返回值
        $data = $client->recv(1024);

        $client->close();

        unset($client);
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
            $this->errorReturn($Validate->getError());
        }
        return $data;
    }

}