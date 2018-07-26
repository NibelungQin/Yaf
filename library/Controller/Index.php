<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/3/21
 * Time: 下午3:21
 */
namespace Controller;

class Index extends \Yaf_Controller_Abstract
{
    // 后台用户编号
    protected $admin_id;
    // 后台客户组编号
    protected $client_id;
    // 需要跳过检查的Action
    protected $white_action = [];

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
        $this->ajaxReturn(true, $info, $param, $url);
    }

    public function errorReturn($info='', $param=[], $url='')
    {
        $this->ajaxReturn(false, $info, $param, $url);
    }

    /**
     * ajaxReturn,返回ajax请求
     *
     * @param array 数组
     * @return json 字符串
     */
    public function ajaxReturn($status,$info,$param,$url,$encoding = 'utf-8')
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
            $this->errorReturn($Validate->getError());
        }
        return $data;
    }
}