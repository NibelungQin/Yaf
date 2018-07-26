<?php
/**
 * 微信消息处理
 * User: marico
 * Date: 2017/4/5
 * Time: 下午5:29
 */
namespace Wechat;
use Wechat\Keyword\ListModel;

class Message
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
        $method = $data['MsgType'];
        // 获取消息类型
        $type_id = self::getTypeId($method);
        // 判断消息类型是否存在
        if (empty($type_id))
        {
            return false;
        }
        // 构造where，并查询
        $where = [
            'public_id' => $data['public_id'],
            'type' => ['in', [0, $type_id]],
        ];
        $list = self::getData($where);
        // 判断是否有关键词设置
        if (!is_array($list))
        {
            return false;
        }
        // 根据关键词匹配情况，返回规则id
        $rule_id = self::$method($data, $list);
        // 若为false，则使用默认回复
        $rule_id === false && $rule_id = 2;
        // 返回数据
        return $rule_id;
    }

    /**
     * 处理文本关键词
     * @param array $data
     * @param array $list
     * @return string
     */
    private static function text(Array $data=[], Array $list=[])
    {
        // 循环数据
        foreach ($list as $value)
        {
            // 关键词解析
            $keywords = explode(',', $value['keyword']);
            // 循环关键词
            foreach ($keywords as $word)
            {
                // 判断是否需要完全匹配
                if (empty($value['need_equal'])
                    && strpos($data['Content'], $word) !== false)
                {
                    return $value['rule_id'];
                }
                // 若需要完全匹配，则进行全匹配
                if ($word == $data['Content'])
                {
                    return $value['rule_id'];
                }
            }
        }
        return false;
    }

    /**
     * 根据类型获取处理ID
     * @param string $type
     * @param none
     * @return int
     */
    public static function getTypeId($type='')
    {
        // 若存在，则返回编号
        if (isset(self::$type[$type]))
        {
            return self::$type[$type];
        }
        // 若不存在，则返回0
        return 0;
    }

    /**
     * 查询数据库keyword设置
     * @param array $where
     * @param none
     * @return array
     */
    private static function getData(Array $where=[])
    {
        // 当前时间
        $now = time();
        // 构建查询条件
        $where = array_merge($where, [
            'status' => 1,
            'start_time' => ['elt', $now],
            'end_time' => ['egt', $now],
        ]);
        // 获取关键词列表
        return ListModel::findAll($where);
    }

    /**
     * 处理图片关键词
     * @param array $data
     * @param array $list
     * @return string
     */
    private static function image(Array $data=[], Array $list=[])
    {
        return false;
    }

    /**
     * 处理语音关键词
     * @param array $data
     * @param array $list
     * @return string
     */
    private static function voice(Array $data=[], Array $list=[])
    {
        // 判断是否开启语音翻译
        if (!isset($data['Recognition']))
        {
            return false;
        }
        // 循环数据
        foreach ($list as $value)
        {
            // 关键词解析
            $keywords = explode(',', $value['keyword']);
            // 循环关键词
            foreach ($keywords as $word)
            {
                // 判断是否需要完全匹配
                if (empty($value['need_equal'])
                    && strpos($data['Recognition'], $word) !== false)
                {
                    return $value['rule_id'];
                }
                // 若需要完全匹配，则进行全匹配
                if ($word == $data['Recognition'])
                {
                    return $value['rule_id'];
                }
            }
        }
        return false;
    }

    /**
     * 处理视频关键词
     * @param array $data
     * @param array $list
     * @return string
     */
    private static function video(Array $data=[], Array $list=[])
    {
        return false;
    }

    /**
     * 处理小视频关键词
     * @param array $data
     * @param array $list
     * @return string
     */
    private static function shortvideo(Array $data=[], Array $list=[])
    {
        return false;
    }

    /**
     * 处理地点关键词
     * @param array $data
     * @param array $list
     * @return string
     */
    private static function location(Array $data=[], Array $list=[])
    {
        return false;
    }

    /**
     * 处理链接关键词
     * @param array $data
     * @param array $list
     * @return string
     */
    private static function link(Array $data=[], Array $list=[])
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