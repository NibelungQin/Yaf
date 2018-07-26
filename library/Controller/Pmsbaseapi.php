<?php
/**
 * 后台管理 接口模式通用api
 * User: marico
 * Date: 2017/3/18
 * Time: 下午7:34
 */
namespace Controller;

class Pmsbaseapi extends \Yaf_Controller_Abstract
{
    // 后台用户编号
    protected $admin_id;
    //是否登录
    protected $is_admin;
    //是否登录
    protected $project_ids;
    //用户数据
    protected $admin;
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
        \Yaf_Dispatcher::getInstance()->disableView();
        //获取session里面数据
        $this->getsession();
        // 定义全局常量
        $this->defineAll();
        // 管理员是否登录判断
        $this->isLogin();
         //权限管理判断
        $this->checkPurview();
        //数据权限
        $this->dataPurview();
        //日志记录
        $this->recordLog($rid=1);
    }
    /**
     * 获取session缓存里面值
     */
    protected function getsession()
    {
        //获取用户数据
        $admin = $this->_session->get('admin');
        $this->admin = $admin;
        //获取后台用户编号
        if (!empty($admin) && isset($admin['id']))
                            $this->admin_id = $admin['id'];
        //是否登录
        $is_admin = $this->_session->get('is_admin');
        $this->is_admin = $is_admin;
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
     * 记录日志信息
     */
    protected function recordLog($rid='')
    {
        //模块数据
        $data['module'] = MODULE;
        $data['controller'] = CONTROLLER;
        $data['action'] = ACTION;
        //用户编号数据
        $data['admin_id'] = $this->admin_id;
        //操作的信息编号id
        $data['rid'] = $rid;
        //插入到日志表
        \Pmsadmin\LogModel::create($data);
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
     * 过滤控制器
     */
    private function filter()
    {
        $_C=[
               'Login-code','Login-logout','Login-login','Admin-menu','Project-select','Cplace-qcode',
               'Upload-image','Upload-picture','Upload-ueditor',
            ];

        $_Ctemp= CONTROLLER.'-'.ACTION;
        if( in_array($_Ctemp, $_C) ){ return true;}
    }
    /**
     * 管理员是否登录判定
     * @param none
     * @return bool
     */
    protected function isLogin()
    {
        //过滤控制器
        $judge = $this->filter();
        if($judge==true) return ;
    	// 获取登录状态
        $is_admin = $this->is_admin;
        //获取用户数据
        $admin = $this->admin;
    	// 判断是否已登录
    	if (empty($is_admin))
    	{
            $this->ajaxReturn(102,'没有登录');
    	}
    	else
    	{
            empty($admin) && $this->ajaxReturn(102,'没有登录');
    	}
    }
    /**
     * 判断管理员是否有模块查看的权限
     * @param array $admin
     * @return html / bool
     */
    private function dataPurview()
    {
        $admin = $this->admin;
        //从session获取权限数据
        $project_ids = $this->_session->get('project_ids');
        //判断session是否存在权限数据
        if(empty($project_ids)&&$admin['level'] >= 1)
        {
            //查询条件
            $param['admin_id'] = $this->admin_id;
            //从数据库查询数据
            $project_ids = \Pmsadmin\AdmintodataModel::Projectdata($param);
            $project_ids = implode(',',$project_ids);
            //权限数据存入session
            $this->_session->set('project_ids',$project_ids);
        }
        //设置数据权限
        $this->project_ids = $project_ids;
    }
    /**
     * 判断管理员是否有模块查看的权限
     * @param array $admin
     * @return html / bool
     */
    private function checkPurview()
    {
        //过滤控制器
        $judge = $this->filter();
        if($judge==true) return ;
        //获取用户数据
        $admin = $this->admin;
        // 超级管理员,跳过权限检查
        if ($admin['level'] < 1)
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
        $count = \Pmsadmin\AdmintosourceModel::checkAdminSource($param);
        // 若存在，则完成检查
        if (!empty($count))
        {
            return true;
        }

        $this->ajaxReturn(103,'您没有访问权限');
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
        	$this->ajaxReturn(101,'参数传递错误!');
        }
        return $data;
    }

}