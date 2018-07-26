<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/4/21
 * Time: 上午10:00
 */
namespace Wechat;

class Event
{
    private static $type = [
        'text' => 1,
        'image' => 2,
        'voice' => 3,
        'video' => 4,
        'shortvideo' => 5,
        'location' => 6,
        'link' => 7,
    ];

    /**
     * 处理消息
     * @param array $data
     * @param none
     * @return mixed
     */
    public static function analysis(Array $data=[])
    {
        // 根据消息类型，调用相应的处理function
        $method = strtolower($data['Event']);
        // 根据关键词匹配情况，返回规则id
        $rule_id = self::$method($data);
//        // 若为false，则使用默认回复
//        $rule_id === false && $rule_id = 2;
        // 返回数据
        return $rule_id;
    }

    /**
     * 处理用户关注事件
     * @param array $data
     * @param none
     * @return string
     */
    private static function subscribe(Array $data=[])
    {
        // 判断是否为扫码关注，若为扫码关注，则交由scan处理
        if (isset($data['Ticket']))
        {
            return self::scan($data);
        }

        return 1; // 关注时回复
    }

    /**
     * 处理用户取消关注事件
     * @param array $data
     * @param none
     * @return string
     */
    private static function unsubscribe(Array $data=[])
    {
        return false;
    }

    /**
     * 处理用户点击自定义菜单事件
     * @param array $data
     * @param none
     * @return string
     */
    private static function click(Array $data=[])
    {
        return false;
    }

    /**
     * 处理用户点击自定义菜单，跳转URL事件
     * @param array $data
     * @param none
     * @return string
     */
    private static function view(Array $data=[])
    {
        return false;
    }

    /**
     * 处理用户扫二维码事件
     * @param array $data
     * @param none
     * @return string
     */
    private static function scan(Array $data=[])
    {
        return false;
    }

    /**
     * 处理用户定位上报事件
     * @param array $data
     * @param none
     * @return string
     */
    private static function location(Array $data=[])
    {
        return false;
    }

    /**
     * 魔术方法，当调用未定义function时
     * @param string $name
     * @param array $arguments
     * @return string
     */
    public static function __callStatic($name='', $arguments=[])
    {
        return false;
    }
}