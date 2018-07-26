<?php
/**
 * HTTP封装
 * User: Marico
 * Date: 16/2/26
 * Time: 15:51
 */
class Http
{
    /**
     * URL访问请求，进行GET请求
     * @param string $url 目标地址
     * @param string $callFunc 请求参数
     * @return $resutl 请求返回结果
     */
    public static function get($url='', $callFunc='')
    {
        $ch = curl_init ();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = $callFunc instanceof \Closure ? $callFunc($result) : $result;
        return $result;
    }

    /**
     * URL访问请求，进行POST请求
     * @param string $url
     * @param array $data
     * @param string $callFunc
     * @return mixed
     */
    public static function post($url='', $data=[], $callFunc='')
    {
        is_array($data) && $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $ch = curl_init ();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec ( $ch );
        curl_close ( $ch );
        $result = $callFunc instanceof \Closure ? $callFunc($result) : $result;
        return $result;
    }

    /**
     * 获取真实IP
     * @param none
     * @return mixed
     */
    public static function get_client_ip()
    {
        $unknown = 'unknown';

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
            && $_SERVER['HTTP_X_FORWARDED_FOR']
            && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)
        )
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        elseif(isset($_SERVER['REMOTE_ADDR'])
            && $_SERVER['REMOTE_ADDR']
            && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)
        )
        {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        /*
        处理多层代理的情况
        或者使用正则方式：$ip = preg_match("/[\d\.]{7,15}/", $ip, $matches) ? $matches[0] : $unknown;
        */
        if (false !== strpos($ip, ',')) $ip = reset(explode(',', $ip));
        return $ip;
    }
}