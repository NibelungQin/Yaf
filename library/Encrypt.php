<?php
/**
 * Created by PhpStorm.
 * User: Marico
 * Date: 16/6/24
 * Time: 10:50
 */
class Encrypt
{
    // 加密秘钥字符串
    protected static $key = 'kfw001_key';
    /**向量
    * @var string
    */
    protected static $IV = "123456ljw7890123412";
    // 字符替换规则
    protected static $rule = [
        '+' => '*',
        '/' => ':',
        '=' => '_',
    ];
    /***
    * @$operation  =DECODE 解码  其它值：加密
    * @$key 加密的密钥
    * @$expiry 有效时间
    * @$string  需要加密的字符串
    */
    public static function authcode($string, $operation ='DECODE', $key ='', $expiry = 0){

    	if($operation=='DECODE'){
    		$string=self::des_replace($string,false);   
    	}
    	if(empty($key)) $key=self::$key;
    	//动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
    	$ckey_length = 4;
    	// 密匙
    	$key = md5(self::$key);
    	//密匙a会参与加解密
    	$keya = md5(substr($key, 0, 16));
    	// 密匙b会用来做数据完整性验证
    	$keyb = md5(substr($key, 16, 16));
    	// 密匙c用于变化生成的密文
    	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
    	// 参与运算的密匙
    	$cryptkey = $keya.md5($keya.$keyc);
    	$key_length = strlen($cryptkey);
    	// 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性
    	// 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
    	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
    	$string_length = strlen($string);
    	$result = '';
    	$box = range(0, 255);
    	$rndkey = array();
    	// 产生密匙簿
    	for($i = 0; $i <= 255; $i++) {
    		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
    	}
    	// 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
    	for($j = $i = 0; $i < 256; $i++) {
    		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
    		$tmp = $box[$i];
    		$box[$i] = $box[$j];
    		$box[$j] = $tmp;
    	}
    	// 核心加解密部分
    	for($a = $j = $i = 0; $i < $string_length; $i++) {
    		$a = ($a + 1) % 256;
    		$j = ($j + $box[$a]) % 256;
    		$tmp = $box[$a];
    		$box[$a] = $box[$j];
    		$box[$j] = $tmp;
    		// 从密匙簿得出密匙进行异或，再转成字符
    		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    	}
    	if($operation == 'DECODE') {
    		// substr($result, 0, 10) == 0 验证数据有效性
    		// substr($result, 0, 10) - time() > 0 验证数据有效性
    		// substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16) 验证数据完整性
    		// 验证数据有效性，请看未加密明文的格式
    		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
    			return substr($result, 26);
    		} else {
    			return '';
    		}
    	} else {
    		//把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
    		//因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
    		$info=$keyc.str_replace('=', '', base64_encode($result));
    		return self::des_replace($info);
    	}
    
    }
	/***
     * 本函数用于php7.0以上版本
     * @解码数据
     */
    public static function undesWithOpenssl($str,$key='',$iv =''){
    	echo $str."</br>";
    	if(empty($key)) $key=self::$key;
    	if(empty($iv)) $iv=self::$IV;
    	$encrypt = json_decode(base64_decode($str), true);
    	$iv =$encrypt['iv'];
    	$des_string =base64_decode($encrypt['value']);
    	return json_decode(openssl_decrypt($des_string,"AES-128-CBC",$key,OPENSSL_RAW_DATA,$iv),true);
    }
    /***
    *本函数用于php7.0以上版本
    *@加密数据
    */
    public static function desWithOpenssl($str,$key ='',$iv =''){
    	$str=json_encode($str);
    	$data['iv']=$iv;
    	if(empty($key)) $key=self::$key;
    	if(empty($data['iv'])){
    		$data['iv']=substr(base64_encode(time().self::$IV),4,16); //每次都不一样的
    	}
    	$data['value']=base64_encode(openssl_encrypt($str,"AES-128-CBC",$key,OPENSSL_RAW_DATA,$data['iv']));
    	return  base64_encode(json_encode($data));
    }
    /**
     * 用户密码加密规则
     * @param $str
     * @return mixed
     */
    public static function md5Pwd($str='')
    {
        return md5(sha1($str).self::$key);
    }

    /**
     *
     * @param $str 需要替换的字符串
     * @param bool|true $is_encrypt 是否为加密
     * @return $str 替换结果字符串
     */
    public static function des_replace($str, $is_encrypt=true)
    {
        // 若为加密,不交换替换键值;若为解密,交换替换键值
        $rule = $is_encrypt ? self::$rule : array_flip(self::$rule);
        foreach($rule as $k => $v)
        {
            $str = str_replace($k,$v,$str);
        }
        return $str;
    }

    /**
     * 数据加密
     * @param $input 输入的值,待加密内容
     * @return string 加密后的内容
     */
    public static function des($input, $key='')
    {
        $key = empty($key)?self::$key:$key;
        $size = mcrypt_get_block_size(MCRYPT_3DES, 'ecb');
        $input = self::des_pkcs5_pad($input, $size);
        $key = str_pad($key,24,'0');
        $td = mcrypt_module_open(MCRYPT_3DES, '', 'ecb', '');
        $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return self::des_replace($data);
    }

    /**
     * 数据解密
     * @param $encrypted 加密后的字符串
     * @return bool|string
     */
    public static function undes($encrypted, $key='')
    {
        $key = empty($key)?self::$key:$key;
        $encrypted = self::des_replace($encrypted, false);
        $encrypted = base64_decode($encrypted);
        $key = str_pad($key,24,'0');
        $td = mcrypt_module_open(MCRYPT_3DES,'','ecb','');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td),MCRYPT_RAND);
        //$ks = mcrypt_enc_get_key_size($td);
        mcrypt_generic_init($td, $key, $iv);
        $decrypted = mdecrypt_generic($td, $encrypted);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return self::des_pkcs5_unpad($decrypted);
    }

    /**
     * pkcs5加密
     * @param $text
     * @param $blocksize
     * @return string
     */
    public static function des_pkcs5_pad ($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    /**
     * pkcs5解密
     * @param $text
     * @return bool|string
     */
    public static function des_pkcs5_unpad($text)
    {
        $pad = ord($text{strlen($text)-1});
        if ($pad > strlen($text))
        {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
        {
            return false;
        }
        return substr($text, 0, -1 * $pad);
    }

    /**
     * 压缩处理(非绝对加密)
     * @param string $str
     * @param none
     * @return none
     */
    public static function zip_str($str='')
    {
        is_string($str) || $str = serialize($str);
        $data = base64_encode(gzcompress($str, 9));
        return self::des_replace($data);
    }

    /**
     * 解压缩处理(非绝对加密)
     * @param string $str
     * @param none
     * @return none
     */
    public static function unzip_str($str='')
    {
        $str = self::des_replace($str, false);
        return unserialize(gzuncompress(base64_decode($str)));
    }

    /**
     * 随机生成验证码
     * @param int $length
     * @return string $key 验证码
     */
    public static function randomCode($length=6,$ischars=true)
    {
        $key = '';
        $_len=61;
        if($ischars==true){
        	$str 	='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }else{
        	$str 	='0123456789';
        	$_len=9;
        }
        for($i = 0; $i < $length; $i++)
        {
        	$key .= $str[mt_rand(0,$_len)];
        }
        return $key;
    }
}