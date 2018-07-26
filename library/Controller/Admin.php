<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/3/18
 * Time: 下午7:34
 */
namespace Controller;
use Rbac\AdmintosourceModel;

class Admin extends \Yaf_Controller_Abstract
{
    // 后台用户编号
    protected $admin_id;
    // 后台客户组编号
    protected $client_id;
    // 公众号编号
    protected $public_id;
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
        $this->defineAll();
        // 管理员是否登录判断
        $this->isLogin();
        // 判断是否为微信模块，需选择操作微信
        $this->checkWechat();
    }

    /**
     * success/error,返回页面错误
     * @param string $info 信息
     * @param string/array $param 参数
     * @param string $url 跳转地址
     * @return json 字符串
     */
    protected function success($info='', $param=[], $url='')
    {
        $this->tips(true, $info, $param, $url);
    }

    protected function error($info='', $param=[], $url='')
    {
        $this->tips(false, $info, $param, $url);
    }
    protected function tips($status=false, $info='', $param=[], $url='')
    {
        throw new \Exception($info);
    }

    /**
     * successReturn/errorReturn,返回ajax请求
     *
     * @param string $info 信息
     * @param string/array $param 参数
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

    /**
     * ajaxReturn,返回ajax请求
     * @param bool $status
     * @param string $info
     * @param array $param
     * @param string $url
     * @param string $encoding
     */
    protected function ajaxReturn($status=false,$info='',$param=[],$url='',$encoding = 'utf-8')
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
    protected function defineAll()
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
    protected function isLogin()
    {
        // 获取登录状态
        $is_admin = $this->_session->get('is_admin');
        // 判断是否已登录
        if (empty($is_admin))
        {
            if (!(MODULE == 'System'
                && CONTROLLER == 'Login'))
            {
                $this->redirect(\Url::to('System/Login/index'));
            }
        }
        else
        {
            // 若已登录，则判断是否为登录页，跳转到后台首页
            if (MODULE == 'System'
                && CONTROLLER == 'Login'
                && ACTION == 'index')
            {
                $this->redirect(\Url::to('System/index/index'));
            }
            // 获取用户信息
            $admin = $this->_session->get('admin');
            empty($admin) && $this->error('缺少用户信息');
            // 用户编号
            $this->admin_id = $admin['id'];
            $this->client_id = $admin['client_id'];
            // 权限管理判断
            $this->checkPurview($admin);
        }
    }

    /**
     * 检查是否为微信模块
     * 微信模块需要选择操作的公众号
     * @param none
     * @param none
     * @return none
     */
    private function checkWechat()
    {
        // 判断是否在wechat模块下
        if (MODULE == 'Wechat' && CONTROLLER != 'Public')
        {
            // 获取当前选择的公众号编号
            $this->public_id = $this->_session->get('public_id');
            // 不存在，则跳转至选择界面
            if (empty($this->public_id))
            {
                // $this->error('请先选择操作的公众账号');
                $this->redirect(\Url::to('Public/list'));
            }
        }
    }

    /**
     * 判断管理员是否有模块查看的权限
     * @param array $admin
     * @return html / bool
     */
    private function checkPurview(Array $admin=[])
    {
        // 超级管理员,跳过权限检查
        if ($admin['level'] < 2)
        {
            return true;
        }
        // 若是Admin模块，公共可访问
        if (MODULE == 'System' || in_array(ACTION, $this->white_action))
        {
            return true;
        }
        // 检查权限
        $param = [
            'admin_id' => $this->admin_id,
            'module' => MODULE,
            'controller' => CONTROLLER,
            'action' => ACTION,
            'status' => 1,
        ];
        $count = AdmintosourceModel::checkSource($param);
        // 若存在，则完成检查
        if (!empty($count))
        {
            return true;
        }
        // 判断是否为ajax访问
        if ($this->_req->isXmlHttpRequest())
        {
            $this->errorReturn('您没有权限访问');
        }
        return $this->error('您没有权限访问');
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