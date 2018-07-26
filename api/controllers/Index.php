<?php

/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/3/17
 * Time: 下午5:40
 */
use Controller\Index;
class IndexController extends Index
{
    /**
     * 初始化控制器
     */
    public function init()
    {
        // 结束
        die('欢迎访问API接口系统');
        // parent::init();
    }
}