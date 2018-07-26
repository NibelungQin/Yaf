<?php
/**
 * 当有未捕获的异常, 则控制流会流到这里
 * @param none
 * @param none
 * @return none
 */
class ErrorController extends Yaf_Controller_Abstract
{
    /**
     * 初始化控制器
     * @param none
     * @param none
     * @return none
     */
    public function init() 
    {
        // 关闭视图渲染
        Yaf_Dispatcher::getInstance()->disableView();
    }

    /**
     * 异常处理控制器
     * @param Exception $exception
     * @param none
     * @return none
     */
    public function errorAction(Exception $exception)
    {
        // 打印输出
        die($exception->getMessage());
    }
}
