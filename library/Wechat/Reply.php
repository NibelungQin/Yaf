<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/4/5
 * Time: 下午5:38
 */
namespace Wechat;

use Wechat\Keyword\ReplyModel;

class Reply
{
    /**
     * 分析回复情况
     * @param int $rule_id
     * @param array $data
     * @return string
     */
    public static function analysis($rule_id=2, Array $data=[])
    {
        // 查询回复数据
        $reply = ReplyModel::get([
            'rule_id' => $rule_id,
            'public_id' => $data['public_id'],
        ], false);
        // 判断查询是否成功
        if (empty($reply))
        {
            return false;
        }
        // 分析需要reply的内容
        $method = $reply['type'];
        // 准备基础数据
        $data = [
            'ToUserName' => $data['FromUserName'],
            'FromUserName' => $data['ToUserName'],
            'CreateTime' => time(),
            'MsgType' => $method,
        ];
        // 解码
        $reply = json_decode($reply['content'], true);
        // 处理返回的array数据
        return self::make(array_merge($data, $reply));
    }

    /**
     * 制作回复，防止未来修改回复格式
     * @param array $reply
     * @param none
     * @return string
     */
    private static function make(Array $reply)
    {
        return \Xml::make($reply);
    }
}