<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/4/6
 * Time: 下午5:55
 */
namespace Wechat;

class Menu
{
    // url接口地址
    private static $url = [
        'create' => 'https://api.weixin.qq.com/cgi-bin/menu/create',
        'get' => 'https://api.weixin.qq.com/cgi-bin/menu/get',
        'delete' => 'https://api.weixin.qq.com/cgi-bin/menu/delete',
        'addconditional' => 'https://api.weixin.qq.com/cgi-bin/menu/addconditional',
        'delconditional' => 'https://api.weixin.qq.com/cgi-bin/menu/delconditional',
        'trymatch' => 'https://api.weixin.qq.com/cgi-bin/menu/trymatch',
        'get_current_selfmenu_info' => 'https://api.weixin.qq.com/cgi-bin/get_current_selfmenu_info',
    ];
    // 错误信息
    private static $error_msg = '';

    /**
     * 创建自定义菜单
     * @param array $data
     * @param array $public
     * @return bool
     */
    public static function create($data=[], $public=[])
    {
        // 制作URL
        $url = self::makeUrl('create', $public);
        // 进行数据请求
        $result = \Http::post($url, $data, function($res){
            return json_decode($res, true);
        });
        // 判断请求结果
        if (empty($result))
        {
            self::$error_msg = '请求微信服务器失败';
            return false;
        }
        // 判断是否执行成功
        if (isset($result['errcode']) && $result['errcode'] != 0)
        {
            self::$error_msg = $result;
            return false;
        }
        // 返回数据
        return $result;
    }

    /**
     * 获取自定义菜单
     * @param array $public
     * @param none
     * @return json
     */
    public static function get($public=[])
    {
        // 制作URL链接
        $url = self::makeUrl('get', $public);
        // 进行http请求
        $result = \Http::get($url, function($res){
            return json_decode($res, true);
        });
        // 返回数据判断
        if (empty($result))
        {
            self::$error_msg = '请求微信服务器失败';
            return false;
        }
        // 判断是否执行成功
        if (isset($result['errcode']) && $result['errcode'] != 0)
        {
            self::$error_msg = $result;
            return false;
        }
        // 返回数据
        return $result;
    }

    /**
     * 删除自定义菜单
     * @param array $public
     * @param none
     * @return json
     */
    public static function delete($public=[])
    {
        // 制作URL链接
        $url = self::makeUrl('delete', $public);
        // 进行http请求
        $result = \Http::get($url, function($res){
            return json_decode($res, true);
        });
        // 返回数据判断
        if (empty($result))
        {
            self::$error_msg = '请求微信服务器失败';
            return false;
        }
        // 判断是否执行成功
        if ($result['errcode'] != 0)
        {
            self::$error_msg = $result;
            return false;
        }
        // 返回数据
        return $result;
    }

    /**
     * 获取错误信息
     * @param none
     * @param none
     * @return string
     */
    public static function getErrorMsg()
    {
        // 返回错误信息
        return self::$error_msg;
    }

    /**
     * 制作请求URL地址
     * @param string $urlKey
     * @param array $public
     * @return string
     */
    private static function makeUrl($urlKey='', $public=[])
    {
        return self::$url[$urlKey].'?access_token='.self::getAccessToken($public);
    }

    /**
     * 获取access_token
     * @param array $public
     * @return string
     */
    private static function getAccessToken($public=[])
    {
        return Open::publicAccessToken($public);
    }

    /**
     * 钩子，魔法函数
     * @param $name
     * @param $arguments
     * @return bool
     */
    public static function __callStatic($name='', $arguments=[])
    {
        // 判断是否存在
//        if (isset(self::$url[$name]))
//        {
//            return false;
//        }
        return false;
    }


}