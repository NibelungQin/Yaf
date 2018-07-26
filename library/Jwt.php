<?php

/**
 * JSON Web Token
 * @author ljw
 */
class Jwt
{
	
	private  $allow='.kfw001.com';
	private static $obj; //被管控的ip
	
	
	protected $option=[
		'secret'=>'kfwljw32442rqewrqewrcasfdsa',		//加密方式
		'verb'	=>'POST',
		'post'	=>'php://input',
	 	'typ'	=>'JWT',
		'alg'	=>'HS256',
		'ttl'	=>'30',		//用来计算   iat+ttl= exp=jwt的过期时间，这个过期时间必须要大于签发时间   iat(签发时间)
		'leeway'=>3,
		
// 		'playload'=>[
// 			'iss'=>'kfw',	// jwt签发者
// 			'sub'=>'kfw',	// jwt所面向的用户
// 			'aud'=>'kfw',	// 接收jwt的一方
// 			'exp'=>0,		// jwt的过期时间，这个过期时间必须要大于签发时间
// 			'nbf'=>0,		// 定义在什么时间之前，该jwt都是不可用的.
// 			'iat'=>0,		//  jwt的签发时间
// 			'jti'=>'0',		//: jwt的唯一身份标识，主要用来作为一次性token,从而回避重放攻击。
// 		],

	];
	
	/****
	 * 获取一个单例
	*/
	public static  function getInstance($options = []){
		$obj=new jwt($options);
		return $obj;
	}
	
    /**
    * 构造函数
    * @param array $options jwt参数配置
    * @access public
    */
    public function __construct($options = [])
    {
    	//合并
    	if (!empty($options)) {
    		$this->options = array_merge($this->options,$options);
    	}
    	$this->options['method']=$_SERVER['REQUEST_METHOD'];
    	
     }
    /***
    * 产生一个jwt 
    */
    public function generateToken($parm) {
    	
    	
    	$algorithms = array('HS256'=>'sha256','HS384'=>'sha384','HS512'=>'sha512');
    	$header = array();
    	$header['typ']=$this->option['typ'];
    	$header['alg']=$this->option['alg'];
    	
    	$token = array();
    	$token[0] = rtrim(strtr(base64_encode(json_encode((object)$header)),'+/','-_'),'=');
    	
    	//签发时间
    	$claims=[];
    	$claims['iat'] 		=time();	
    	//过期时间
    	$claims['exp'] 		=$claims['iat'] + $this->option['ttl'];
    	$claims['param']	=$parm;
    	
    	
    	$token[1] = rtrim(strtr(base64_encode(json_encode((object)$claims)),'+/','-_'),'=');
    	
    	$hmac = $algorithms[$header['alg']];
    	
    	
    	$signature = hash_hmac($hmac,"$token[0].$token[1]",$this->option['secret'],true);
    	
    	$token[2] = rtrim(strtr(base64_encode($signature),'+/','-_'),'=');
    	
    	return implode('.',$token);
    
    }
    /***
    * 验证一个token的有效性 
    */
    public function getVerifiedClaims($token) {
      	
    	$algorithms = array('HS256'=>'sha256','HS384'=>'sha384','HS512'=>'sha512');
    	
      	if (!isset($algorithms[$this->option['alg']])) return false;
    	
    	$hmac = $algorithms[$this->option['alg']];
    	
    	$token = explode('.',$token);
    	
    	if (count($token)<3) return false;
    	
    	$header = json_decode(base64_decode(strtr($token[0],'-_','+/')),true);
    	
    	if ($header['typ']!='JWT') return false;
    	if ($header['alg']!=$this->option['alg']) return false;
    	
    	$signature = bin2hex(base64_decode(strtr($token[2],'-_','+/')));
    	
    	if ($signature!=hash_hmac($hmac,"$token[0].$token[1]",$this->option['secret'])) return false;
    	
    	//获取载荷数据 
    	$claims = json_decode(base64_decode(strtr($token[1],'-_','+/')),true);
    	if (!$claims) return false;
    	
    	
    	//当前时间
    	$time=time();
    	$leeway		=$this->option['leeway']; //过期
    	$ttl		=$this->option['ttl']; //过期
    	
    	//nbf定义在什么时间之前，该jwt都是不可用的
    	if (isset($claims['nbf']) && $time+$leeway<$claims['nbf']) return false;
    	// jwt的签发时间
    	if (isset($claims['iat']) && $time+$leeway<$claims['iat']) return false;
    	//  jwt的过期时间，这个过期时间必须要大于签发时间
    	if (isset($claims['exp']) && $time-$leeway>$claims['exp']) return false;
    	
    	// jwt的签发时间
    	if (isset($claims['iat']) && !isset($claims['exp'])) {
    		
    		if ($time-$leeway>$claims['iat']+$ttl) return false;
    		
    	}
    	return $claims;
    	
    }
    /***
     * 避免csrf跨站攻击必须加入这个内容
     */
    public function hasValidCsrfToken() {
    	
    	$csrf = isset($_SESSION['csrf'])?$_SESSION['csrf']:false;
    	if (!$csrf) return false;
    	
    	$get = isset($_GET['csrf'])?$_GET['csrf']:false;
    	$header = isset($_SERVER['HTTP_X_XSRF_TOKEN'])?$_SERVER['HTTP_X_XSRF_TOKEN']:false;
    	return ($get == $csrf) || ($header == $csrf);
    	
    }
    /***
     * 跨域处理
     */
    protected function allowOrigin($origin,$allowOrigins=null) {
    	if (isset($_SERVER['REQUEST_METHOD'])) {
    		header('Access-Control-Allow-Credentials: true');
    		header('Access-Control-Expose-Headers: X-XSRF-TOKEN');
    		if($allowOrigins){
	    		foreach (explode(',',$allowOrigins) as $o) {
	    			if (preg_match('/^'.str_replace('\*','.*',preg_quote(strtolower(trim($o)))).'$/',$origin)) {
	    				header('Access-Control-Allow-Origin: '.$origin);
	    				break;
	    			}
	    		}
    		}
    		if(strpos($origin,$this->allow)>0){
    			header('Access-Control-Allow-Origin: '.$origin);
    		}
    	}
    }
    
    protected function headersCommand() {
    	$headers = array();
    	$headers[]='Access-Control-Allow-Headers: Content-Type, X-XSRF-TOKEN';
    	$headers[]='Access-Control-Allow-Methods: OPTIONS, GET, PUT, POST, DELETE, PATCH';
    	$headers[]='Access-Control-Allow-Credentials: true';
    	$headers[]='Access-Control-Max-Age: 1728000';
    	if (isset($_SERVER['REQUEST_METHOD'])) {
    		foreach ($headers as $header) header($header);
    	} else {
    		echo json_encode($headers);
    	}
    }
}
