<?php
/**
 * Created by PhpStorm.
 * User: Marico
 * Date: 16/7/11
 * Time: 11:36
 */
class Request
{
    public static $req = NULL;
    public static $instance = NULL;

    /**
     * 单例模型
     * @param $obj 请求对象
     * @return null|static
     */
    public static function getInstance($obj)
    {
        if (!self::$instance instanceof self)
        {
            self::$instance = new self($obj);
        }
        return self::$instance;
    }

    /**
     * 实例化,私有
     */
    private function __construct($obj)
    {
        is_object($obj) && self::$req = $obj;
    }

    /**
     * 不区分POST,GET请求,获取数据
     * @param $key
     * @param $default
     * @return string
     */
    public function get($key='', $default='')
    {
        $temp = [];
        // 若为空，则返回
        if (empty($key))
        {
            return $default;
        }
        else if (is_array($key))
        {
            foreach ($key as $value)
            {
                $temp[$value] = self::$req->get($value, $default);
            }
        }
        else if (is_string($key))
        {
            $temp = self::$req->get($key, $default);
        }
        // 数据过滤
        $this->dataFilter($temp);
        return $temp;
    }

    /**
     * POST请求,获取数据
     * @param $key
     * @param $default
     * @return string
     */
    public function getPost($key='', $default='')
    {
        $temp = [];
        // 若为空，则获取所有
        if (empty($key))
        {
            $temp = self::$req->getPost();
        }
        else if (is_array($key))
        {
            foreach ($key as $value)
            {
                $temp[$value] = self::$req->getPost($value, $default);
            }
        }
        else if (is_string($key))
        {
            $temp = self::$req->getPost($key, $default);
        }
        // 数据过滤
        $this->dataFilter($temp);
        return $temp;
    }

    /**
     * GET请求,获取数据
     * @param $key
     * @param $default
     * @return string
     */
    public function getQuery($key='', $default='')
    {
        $temp = [];
        // 若为空，则获取所有
        if (empty($key))
        {
            $temp = self::$req->getQuery();
        }
        else if (is_array($key))
        {
            foreach ($key as $value)
            {
                $temp[$value] = self::$req->getQuery($value, $default);
            }
        }
        else if (is_string($key))
        {
            $temp = self::$req->getQuery($key, $default);
        }
        // 数据过滤
        $this->dataFilter($temp);
        return $temp;
    }

    /**
     * is_ajax是否为ajax提交
     * @return mixed
     */
    public function is_ajax()
    {
        return self::$req->isXmlHttpRequest();
    }

    /**
     * isAjax是否为ajax提交
     * @return mixed
     */
    public function isAjax()
    {
        return self::$req->isXmlHttpRequest();
    }

    /**
     * 获取模块名称
     * @param
     * @param
     * @return string
     */
    public function getModuleName()
    {
        return self::$req->getModuleName();
    }

    /**
     * 获取模块名称
     * @param
     * @param
     * @return string
     */
    public function getNewModuleName()
    {
        return self::$req->getNewModuleName();
    }

    /**
     * 获取控制器名称
     * @param
     * @param
     * @return string
     */
    public function getControllerName()
    {
        return self::$req->getControllerName();
    }

    /**
     * 获取方法名称
     * @param
     * @param
     * @return string
     */
    public function getActionName()
    {
        return self::$req->getActionName();
    }

    /**
     * 安全过滤
     * @param $data
     */
    private function dataFilter(&$data)
    {
        if (is_string($data))
        {
            $data = htmlspecialchars($data);
        }
        if (is_array($data))
        {
            foreach ($data as $key => $value)
            {
                $this->dataFilter($data[$key]);
            }
        }
    }

    /**
     * 捕捉调用不到的方法
     * @param $func
     * @param array $param
     * @return mixed
     */
    public function __call($func, $param=[])
    {
        return call_user_func_array([self::$req, $func], $param);
    }
}