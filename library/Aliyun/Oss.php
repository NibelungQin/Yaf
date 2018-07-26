<?php
namespace Aliyun;

use Aliyun\Oss\OssClient;
use Aliyun\Oss\Core\OssException;

class Oss
{
    static $config = [];
    static $ossClient = [];

    /**
     * 根据Config配置，得到一个OssClient实例
     *
     * @return OssClient 一个OssClient实例
     */
    public static function getOssClient($bucket = 'master')
    {
        if (isset(self::$ossClient[$bucket]))
        {
            return self::$ossClient[$bucket];
        }

        try
        {
            $config = self::getBucket($bucket);
            return self::$ossClient[$bucket] = new OssClient(
                $config['keyid'],
                $config['keysecret'],
                $config['endpoint'],
                false
            );
        }
        catch (OssException $e)
        {
            printf($e->getMessage() . "\n");
            return null;
        }
    }

    /**
     * 获取配置信息
     * @param string $bucket
     * @return mixed
     */
    public static function getBucket($bucket = 'master')
    {
        // 判断是否已读取配置文件
        if (isset(self::$config[$bucket]))
        {
            return self::$config[$bucket];
        }
        // 赋值并返回
        return self::$config[$bucket] = \Config::get('oss', $bucket);
    }

    /**
     * 获取链接地址
     * @param string $bucket
     * @return string
     */
    public static function getHttpUrl($bucket = 'master')
    {
        $config = self::getBucket($bucket);
        return $config['httpurl'];
    }
}

