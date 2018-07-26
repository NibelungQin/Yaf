<?php
/**
 * Created by PhpStorm.
 * User: Marico
 * Date: 16/4/3
 * Time: 14:15
 */
class Controller_Interface extends Yaf_Controller_Abstract
{
    // appid
    protected $appid = 'wx93250682fd7902eb';
    // secret
    protected $appSecret = '74ec7e8c8d6f318dfccb8868b4b2814c';
    // 加密秘钥
    protected $key = '_Kwf001';
    protected $user_id = 0;

    /**
     * 程序初始化
     */
    public function init()
    {
        // 请求类
        $this->_req = $this->getRequest();
        $this->_req = Request::getInstance($this->_req);
        // session
        $this->_session = Yaf_Session::getInstance();
        // redis 实例
        $this->redis = Cache_Redis::getRedis();
        // 定义全局常量
        $this->define_all();
    }
    /**
     * 获取请求路径
     */
    protected  function get_module()
    {
        $modules = $this->_req->getControllerName();
        return $modules;
    }
    /**用于统计用户行为数据
     * @param $info
     */
    protected function user_log($info)
    {
        //由于swoole关闭，暂时不用日记统计
        return false;
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
        $client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC); //同步阻塞

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
     * 预定义一些全局常量
     * @param null
     * @return null
     */
    protected function define_all()
    {
        define('MODULE', $this->_req->getModuleName());
        define('CONTROLLER', $this->_req->getControllerName());
        define('ACTION', $this->_req->getActionName());
        define('BASE_URL', 'http://wechat.kfw001.com/gowechat/public/');
        define('HTML_HEAD', APP_PATH.'/modules/Layout/head.html');
        define('HTML_FOOT', APP_PATH.'/modules/Layout/foot.html');
    }
    /**
     *微信公众号授权
     */
    protected function _weixinofficalgrant($module =  '', $aid=0)
    {
        $param['module'] = $module;
        $param['id'] = $aid ;

        $list = Admin_AccountsuseractiveModel::findinfo($param);
        //+
        if(!empty($list))
        {
            //查询授权微信公众号
            $oa_data = Weixin_OaModel::find_id($list['oa_id']);
            //如果微信公众号不为空，且oa_appid,oa_secert不为空
            if(!empty($oa_data)&&$oa_data['oa_appid']&&$oa_data['oa_secert'])
            {
                $this->appid = $oa_data['oa_appid'];
            }
        }
        $openid = $this->getOpenid();
        return $openid;
    }
    /**
     * 网页授权处理页,进行跳转
     * @param
     * @return
     */
    protected function getOpenid()
    {
        $openid = $this->_session->get($this->appid.'_user_openid');
        
        $user_id = $this->_session->get('_user_id');
        //标记
        $mark = $this->_session->get('mark');
        if(empty($mark))
        {
            // 记录授权前访问地址
            $REQUEST_URI = $_SERVER['REQUEST_URI'];
            // 记录授权前访问地址
            $this->_session->set('_REQUEST_URI', $REQUEST_URI);
        }
        else
        {
            $REQUEST_URI = $this->_session->get('_REQUEST_URI');
        }
        // 若seesion中存在,则不需要再次授权
        if ( !empty($openid) && !empty($user_id)&!empty($mark) )
        {
            $this->user_id = $user_id;
            $wid = $user_id;
            $timestamp = time();
            $sign = $wid.','.$timestamp.','.$openid;
            $sign = Crypt3Des::encrypt($sign);
            if(stristr($REQUEST_URI,"?"))
            {
                $REQUEST_URI.='&sign='.$sign;
            }
            else
            {
                $REQUEST_URI.='?sign='.$sign;
            }
            header('Location: ' . $REQUEST_URI);
        }
        else if(!empty($openid) && !empty($user_id))
        {
            $this->user_id = $user_id;
            return $openid;
        }
        // 授权回调地址,用于接收时效性code
        $callback = Url::to(
            'Auth/Index/auth',
           ['appid' => $this->appid],
            true
        );
     
        $callback = urlencode($callback);
        $stateKey = substr(md5($REQUEST_URI), 0, 8);

        $forward = 'https://open.weixin.qq.com/connect/oauth2/authorize?'
                  ."appid={$this->appid}&redirect_uri={$callback}&response_type=code"
                  ."&scope=snsapi_userinfo&state={$stateKey}#wechat_redirect";

        header('Location: ' . $forward);

        exit;
    }

    /**
     * 执行失败提示界面
     * @param
     * @return
     */
    protected function error($message='操作失败',$url='',$delay=3000)
    {

        $data = [
            'title' => '操作失败',
            'icon' => 'weui_icon_warn',
            'message' => $message,
            'url' => $url,
            'delay' => $delay
        ];

        $this->tips($data);
    }

    /**
     * 执行成功提示界面
     * @param
     * @return
     */
    protected function success($message='操作成功',$url='',$delay=3000)
    {
        $data = [
            'title' => '操作成功',
            'icon' => 'weui_icon_success',
            'message' => $message,
            'url' => $url,
            'delay' => $delay
        ];

        $this->tips($data);
    }

    /**
     * 提示界面
     * @param $data 传入界面的值
     * @return
     */
    protected function tips($data)
    {
        $template = APP_PATH.'/application/modules/Layout/tips.phtml';

        $this->_view->display($template, $data);

        exit();
    }

    /**
     * successReturn/errorReturn,返回ajax请求
     *
     * @param string $info 信息
     * @param string/array $param 参数
     * @param string $code 状态码
     * @return json 字符串
     */
    protected function successReturn($info='', $param=[], $url='',$callback='')
    {
        $this->ajaxReturn(true, $info, $param, $url,'utf-8',$callback);
    }

    protected function errorReturn($info='', $param=[], $url='',$callback='')
    {
        $this->ajaxReturn(false, $info, $param, $url,'utf-8',$callback);
    }

    /**
     * ajaxReturn,返回ajax请求
     *
     * @param array 数组
     * @return json 字符串
     */
    protected function ajaxReturn($status,$info,$param,$url,$encoding = 'utf-8',$callback='')
    {
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

            if(!empty($param))
            {
                $data['param'] = $param;
            }
            if(!empty($url))
            {
                $data['url'] = $url;
            }
        }
        header("Content-type: application/json;charset=$encoding");
        if(!empty($callback))  die($callback.'('.json_encode($data).')');
         
        die(json_encode($data));
    }

    /**
     * 发送模板消息
     *
     */
    protected function message_template($data=[])
    {
        $Wechat_Token = new Wechat_Token($this->appid,$this->appSecret);

        $ACCESS_TOKEN = $Wechat_Token->get_Access_Token();

        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$ACCESS_TOKEN;

        $res = Http::post($url,$data);

        $res = json_decode($res, true);

        if(isset($res['errcode'])
            && $res['errcode'] == 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}