<?php
use OSS\OssClient;
use OSS\Core\OssException;

class OSS_Common
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
            $config = self::get_bucket($bucket);

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
     * @param string $bucket
     */
    public static function get_bucket($bucket = 'master')
    {
        if (isset(self::$config[$bucket]))
        {
            return self::$config[$bucket];
        }

        return self::$config[$bucket] = Config::get('oss', $bucket);
    }

    /**
     * 获取链接地址
     * @param string $bucket
     * @return string
     */
    public static function get_httpUrl($bucket = 'master')
    {
        $config = self::get_bucket($bucket);

        return $config['httpurl'];
    }
}

