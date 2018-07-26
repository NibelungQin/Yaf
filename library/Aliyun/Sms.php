<?php

/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/6/22
 * Time: 下午3:24
 */
namespace Aliyun;

use Aliyun\Mns\Client;
use Aliyun\Mns\Model\BatchSmsAttributes;
use Aliyun\Mns\Model\MessageAttributes;
use Aliyun\Mns\Exception\MnsException;
use Aliyun\Mns\Requests\PublishMessageRequest;

class Sms
{
    // 签名
    private static $signName = '';
    // 错误码
    private static $errMsg = '';
    // 主题引用
    private static $Topic;
    // 主题code
    private static $Code;  //SMS_27315217
    

    /**
     * 初始化客户端
     * @param none
     * @param none
     * @return none
     */
    private static function init()
    {
        // 判断是否为对象
        if (!is_object(self::$Topic))
        {
            // 读取sms配置文件
            $config = \Config::sms('sms');
            // 完成配置accessKeyId、accessKeySecret
            $Client = new Client($config['endpoint'], $config['keyid'], $config['keysecret']);
            // 赋值签名
            self::$signName = $config['signname'];
            // 获取主题
            self::$Topic = $Client->getTopicRef($config['topicname']);
            
           
            self::$Code = $config['code'];
            
        
            
        }
    }

    /**
     * 发送消息给用户
     * @param array $tels 手机号码
     * @param array $param =['code'=>'验证码','product'=>''] 需要发送的消息          替换模板中的   验证码${code}，您正在进行${product}身份验证，打死不要告诉别人哦！
     * @param $code 模板CODE
     * @return 回复情况
     */
    public static function sendCode($tels,$param,$code='')
    {
    	// 初始化对象
    	self::init();
    
    	if(empty($code)) {$code=self::$Code;}
    	// Step 1. 设置发送短信的签名（SMSSignName）和模板（SMSTemplateCode）
    	$batchSmsAttributes = new BatchSmsAttributes(self::$signName, $code);
    	// Step 2. 设置收消息号码，及模板参数
    	if( is_array($tels)  ){
    		foreach ($tels as $tel){
    			$batchSmsAttributes->addReceiver($tel,$param);
    		}
    	}else{
    		$batchSmsAttributes->addReceiver($tels,$param);
    	}
    	// Step 3. 创建消息主体
    	$messageAttributes = new MessageAttributes([$batchSmsAttributes]);
    	// Step 4. 设置SMS消息体（不能为空，暂时无用）
    	$messageBody = "smsmessage";
    	// Step 5. 发布SMS消息
    	$request = new PublishMessageRequest($messageBody, $messageAttributes);
    	try
    	{
    		$Result = self::$Topic->publishMessage($request);
    		return $Result->getMessageId();
    	}
    	catch (MnsException $e)
    	{
    		print_r($e->getMessage());
    		return self::setErrMsg($e->getMessage());
    	}
    }
    
    /**
     * 发送消息给用户
     * @param array $tels 手机号码
     * @param $msg  需要发送的消息          替换模板中的   验证码${code}，您正在进行${product}身份验证，打死不要告诉别人哦！
     * @param $code 模板CODE
     * @return 回复情况
     */
    public static function send(Array $tels,$msg,$code='')
    {
    	die();
    }

    /**
     * 设置错误信息
     * @param string $message
     * @return bool
     */
    private static function setErrMsg($message='')
    {
        self::$errMsg = $message;
        return false;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public static function getErrMsg()
    {
        return self::$errMsg;
    }
}