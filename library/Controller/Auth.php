<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/4/18
 * Time: 下午7:34
 */
namespace Controller;

class Auth extends \Yaf_Controller_Abstract
{
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
        // 关闭视图渲染
        \Yaf_Dispatcher::getInstance()->disableView();
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
            $this->errorCode(9999, $Validate->getError());
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
}