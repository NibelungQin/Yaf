<?php
/**
 * 微信开放平台基础类库
 * Author: marico
 * Date: 2017/4/1
 * Time: 下午4:39
 */
namespace Wechat;

//use Wechat\AppModel;
//use Wechat\PublicModel;

class Open
{
    // 请求url地址
    private static $url = [
        'access_token' => 'https://api.weixin.qq.com/cgi-bin/component/api_component_token', // APP的access_token
        'auth_code' => 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode',
        'auth_query' => 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth',
        'auth_token' => 'https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token', // 公众号的access_token
        'auth_info' => 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info',
    ];
    // APP配置信息
    private static $app = [
//        'appid' => '',
//        'secret' => '',
//        'verify_ticket' => '',
//        'access_token' => '',
//        'token' => '',
//        'aes_key' => '',
    ];
    // 公众号配置信息
    private static $public = [];

    /**
     * 设置开放平台信息
     * @param mixed $app
     * @param none
     * @return none
     */
    public static function initApp($app=[])
    {
        // 若为空，则默认查询数据库数据
        if (empty($app))
        {
            empty(self::$app) && self::$app = AppModel::get(1, false);
        }
        elseif (is_numeric($app))
        {
            empty(self::$app) && self::$app = AppModel::get($app, false);
        }
        else
        {
            // 合并数组，从而达到替换效果
            self::$app = array_merge(self::$app, $app);
        }
        empty(self::$app) && die('[Wecaht_Open] : error $app id');
    }

    /**
     * 设置public公众号信息
     * @param mixed $public
     * @param none
     * @return none
     */
    public static function initPublic($public=[])
    {
        // 若为空，则默认查询数据库数据
        if (empty($public))
        {
            empty(self::$public) && self::$public = PublicModel::get(1, false);
        }
        elseif (is_numeric($public))
        {
            empty(self::$public) && self::$public = PublicModel::get($public, false);
        }
        else
        {
            // 合并数组，从而达到替换效果
            self::$public = array_merge(self::$public, $public);
        }
        empty(self::$public) && die('[Wecaht_Open] : error $public id');
    }

    /**
     * 获取getAppId
     * @param mixed $app
     * @param none
     * @return string
     */
    public static function getAppId($app=[])
    {
        // TODO 此处需要做数据缓存
        // 组合KEY
        $key = 'APPID_'.md5(serialize($app));
        // 获取数据
        $appid = \Cache::get($key);
        // 判断缓存中是否存在数据
        if (empty($appid))
        {
            // 初始化APP
            self::initApp($app);
            // 赋值
            $appid = self::$app['appid'];
            // 存入缓存，有效期，一天 3600*24
            \Cache::set($key, $appid, 86400);
        }
        // 返回值
        return $appid;
    }

    /**
     * 获取publicAppId
     * @param mixed $public
     * @param none
     * @return string
     */
    public static function publicAppId($public=[])
    {
        // 初始化APP
        self::initPublic($public);
        // 返回值
        return self::$public['appid'];
    }

    /**
     * 获取access_token
     * @param mixed $app
     * @param none
     * @return string
     */
    public static function getAccessToken($app=[])
    {
        // 初始化APP
        self::initApp($app);
        // 当前时间戳
        $now = time();
        // 判断是否还在有效期内，在有效期内则返回
        if (($now - 200) < self::$app['token_expires'])
        {
            return self::$app['access_token'];
        }
        // 准备请求参数
        $data = [
            'component_appid' => self::$app['appid'],
            'component_appsecret' => self::$app['secret'],
            'component_verify_ticket' => self::$app['verify_ticket'],
        ];
        // 进行post请求
        $response = self::httpPost(self::$url['access_token'], $data);
        // 判断请求是否成功，成功则返回access_token
        if (is_array($response) && isset($response['component_access_token']))
        {
            // 若存在APP，id，则更新数据库数据
            if (!empty(self::$app['id']))
            {
                AppModel::update([
                    'access_token' => $response['component_access_token'],
                    'token_expires' => $now + $response['expires_in'],
                ],[
                    'id' => self::$app['id'],
                ]);
            }
            return $response['component_access_token'];
        }
        return false;
    }

    /**
     * 获取public公众号ACCESS_TOKEN
     * @param mixed $public
     * @param none
     * @return string
     */
    public static function publicAccessToken($public=[])
    {
        // 初始化PUBLIC
        self::initPublic($public);
        // 当前时间戳
        $now = time();
        // 判断是否还在有效期内，在有效期内则返回
        if (($now - 200) < self::$public['authorizer_expires'])
        {
            return self::$public['authorizer_access_token'];
        }
        // 构造URL请求地址
        $url = self::$url['auth_token'].'?component_access_token='.self::getAccessToken(self::$public['app_id']);
        // 准备请求参数
        $data = [
            'component_appid' => self::$app['appid'],
            'authorizer_appid' => self::$public['appid'],
            'authorizer_refresh_token' => self::$public['authorizer_refresh_token'],
        ];
        // 进行post请求
        $response = self::httpPost($url, $data);
        // 判断请求是否成功，成功则返回access_token
        if (is_array($response) && isset($response['authorizer_access_token']))
        {
            // 若存在PUBLIC，id，则更新数据库数据
            if (!empty(self::$public['id']))
            {
                PublicModel::update([
                    'authorizer_access_token' => $response['authorizer_access_token'],
                    'authorizer_refresh_token' => $response['authorizer_refresh_token'],
                    'authorizer_expires' => $now + $response['expires_in'],
                ],[
                    'id' => self::$public['id'],
                ]);
            }
            return $response['authorizer_access_token'];
        }
        return false;
    }

    /**
     * 获取public公众号基础信息
     * @param string $appid
     * @param mixed $app
     * @return string
     */
    public static function getPublicInfo($appid='', $app=[])
    {
        // 获取access_token
        $url = self::$url['auth_info'].'?component_access_token='.self::getAccessToken($app);
        $data = [
            'component_appid' => self::$app['appid'],
            'authorizer_appid' => $appid
        ];
        // 进行post请求
        $response = self::httpPost($url, $data);
        // 判断请求是否成功，成功则返回信息
        if (is_array($response) && isset($response['authorizer_info']))
        {
            return $response['authorizer_info'];
        }
        return false;
    }

    /**
     * 获取AuthCode
     * @param mixed $app
     * @param none
     * @return string
     */
    public static function getAuthCode($app=[])
    {
        // 初始化APP
        self::initApp($app);
        // 当前时间戳
        $now = time();
        // 判断是否还在有效期内，在有效期内则返回
        if (($now - 50) < self::$app['auth_expires'])
        {
            return self::$app['auth_code'];
        }
        // 获取access_token
        $url = self::$url['auth_code'].'?component_access_token='.self::getAccessToken($app);
        $data = [
            'component_appid' => self::$app['appid']
        ];
        // 进行post请求
        $response = self::httpPost($url, $data);
        // 判断请求是否成功，成功则返回access_token
        if (is_array($response) && isset($response['pre_auth_code']))
        {
            // 若存在APP，id，则更新数据库数据
            if (!empty(self::$app['id']))
            {
                AppModel::update([
                    'auth_code' => $response['pre_auth_code'],
                    'auth_expires' => $now + $response['expires_in'],
                ],[
                    'id' => self::$app['id'],
                ]);
            }
            return $response['pre_auth_code'];
        }
        return false;
    }

    /**
     * 获取auth公众号信息
     * @param string $code
     * @param mixed $app
     * @return string
     */
    public static function getAuthQuery($code='', $app=[])
    {
        // 获取access_token
        $url = self::$url['auth_query'].'?component_access_token='.self::getAccessToken($app);
        $data = [
            'component_appid' => self::$app['appid'],
            'authorization_code' => $code
        ];
        // 进行post请求
        $response = self::httpPost($url, $data);
        // 判断请求是否成功，成功则返回access_token
        if (is_array($response) && isset($response['authorization_info']))
        {
            return $response;
        }
        return false;
    }

    /**
     * 将公众平台回复用户的消息加密打包.
     * @param string $message
     * @param array $app
     * @return array|bool
     */
    public static function encryptMsg($message='', $app=[])
    {
        // 初始化APP
        self::initApp($app);
        $timestamp = time();
        try
        {
            // 处理key
            $key = base64_decode(self::$app['aes_key'].'=');
            // 获得16位随机字符串，填充到明文之前
            $nonce = self::getRandomStr(16);
            $text = $nonce . pack('N', strlen($message)) . $message . self::$app['appid'];
            // 网络字节序
            // $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
            $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
            $iv = substr($key, 0, 16);
            // 使用自定义的填充方式对明文进行补位填充
            $text = self::PKCS7Encode($text);
            mcrypt_generic_init($module, $key, $iv);
            // 加密
            $encrypted = mcrypt_generic($module, $text);
            mcrypt_generic_deinit($module);
            mcrypt_module_close($module);
            // 使用BASE64对加密后的字符串进行编码
            $encrypt = base64_encode($encrypted);
            // 生成数据签名
            $array = [
                $encrypt, self::$app['token'], $timestamp, $nonce
            ];
            // 排序，拼接，sha1散列
            sort($array, SORT_STRING);
            $signature = sha1(implode($array));
            // 构造返回值,生成发送的xml
            $data = [
                'Encrypt' => $encrypt,
                'MsgSignature' => $signature,
                'TimeStamp' => $timestamp,
                'Nonce' => $nonce,
            ];
            return \Xml::make($data);
        }
        catch (\Exception $e)
        {
            return false;
        }
    }

    /**
     * 消息解密
     * @param Array $data 所有解密数据
     * @param mixed $app 应用信息
     * @return array
     */
    public static function decryptMsg(Array $data, $app=[])
    {
        // 初始化APP
        self::initApp($app);
        // 验证签名是否有效
        if (self::checkSign($data))
        {
            // 对加密数据进行解密
            try
            {
                // 处理key
                $key = base64_decode(self::$app['aes_key'].'=');
                // 使用BASE64对需要解密的字符串进行解码
                $encrypt = base64_decode($data['encrypt']);
                $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
                $iv = substr($key, 0, 16);
                mcrypt_generic_init($module, $key, $iv);
                //解密
                $decrypted = mdecrypt_generic($module, $encrypt);
                mcrypt_generic_deinit($module);
                mcrypt_module_close($module);
                //去除补位字符
                $result = self::PKCS7Decode($decrypted);
                // 去除16位随机字符串,网络字节序和AppId
                if (strlen($result) < 16)
                {
                    return "";
                }
                $content = substr($result, 16, strlen($result));
                $len_list = unpack('N', substr($content, 0, 4));
                $xml_len = $len_list[1];
                $xml_content = substr($content, 4, $xml_len);
                // 判断是否为当前APP应用
                $from_appid = substr($content, $xml_len + 4);
                if (self::$app['appid'] != $from_appid)
                {
                    return false;
                }
            }
            catch (\Exception $e)
            {
                return false;
            }
            // 解密值不控制大小写
            return \Xml::toArray($xml_content, false);
        }
        return false;
    }

    /**
     * 验证签名是否有效
     * @param array $data
     * @param none
     * @return bool
     */
    private static function checkSign(Array $data)
    {
        // 构成签名内容
        $array = [
            $data['encrypt'], self::$app['token'], $data['timestamp'], $data['nonce']
        ];
        // 排序，拼接，sha1散列
        sort($array, SORT_STRING);
        $str = sha1(implode($array));
        // 判断加密参数是否一致
        return $str == $data['msg_signature'] ? true : false;
    }

    /**
     * 对解密后的明文进行补位删除
     * @param string $text 解密后的明文
     * @return string 删除填充补位后的明文
     */
    private static function PKCS7Decode($text='')
    {
        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > 32)
        {
            $pad = 0;
        }
        return substr($text, 0, (strlen($text) - $pad));
    }

    /**
     * 对加密后的明文进行补位
     * @param string $text 解密后的明文
     * @param int $block_size 补位长度
     * @return string 删除填充补位后的明文
     */
    private static function PKCS7Encode($text='', $block_size=32)
    {
        $text_length = strlen($text);
        //计算需要填充的位数
        $amount_to_pad = $block_size - ($text_length % $block_size);
        if ($amount_to_pad == 0)
        {
            $amount_to_pad = $block_size;
        }
        //获得补位所用的字符
        $pad_chr = chr($amount_to_pad);
        $tmp = '';
        for ($index = 0; $index < $amount_to_pad; $index++)
        {
            $tmp .= $pad_chr;
        }
        return $text . $tmp;
    }

    /**
     * 随机生成16位字符串
     * @param int length
     * @param int $max
     * @return string 生成的字符串
     */
    private static function getRandomStr($length=16, $max=0)
	{
		$str = '';
		$str_pol = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        empty($max) && $max = strlen($str_pol) - 1;
		for ($i = 0; $i < $length; $i++) {
			$str .= $str_pol[mt_rand(0, $max)];
		}
        return $str;
    }

    /**
     * URL访问请求，进行POST请求
     * @param $url 目标地址
     * @param $data 请求参数
     * @return $resutl 请求返回结果
     */
    public static function httpPost($url , $data)
    {
        $ch = curl_init ();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        $result = curl_exec($ch);
        curl_close($ch);
        // 请求json解码
        return json_decode($result, true);
    }
}