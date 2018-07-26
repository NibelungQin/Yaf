<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/4/20
 * Time: 下午4:10
 */
class Log
{
    /**
     * 添加日志
     * @param int $type
     * @param array $data
     */
    public static function debug($type=1, $data=[])
    {
        is_array($data) && $data = json_encode($data);
        // 存入数据库
        DB::name('debug')->insert([
            'type' => $type,
            'content' => $data,
        ]);
    }
}