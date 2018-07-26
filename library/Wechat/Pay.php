<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/5/3
 * Time: 上午9:41
 */
namespace Wechat;

class Pay
{
    // 下单接口
    private static $order = [
        'make' => 'https://api.mch.weixin.qq.com/pay/unifiedorder',
        'query' => 'https://api.mch.weixin.qq.com/pay/orderquery',
        'close' => 'https://api.mch.weixin.qq.com/pay/closeorder',
        'refund' => 'https://api.mch.weixin.qq.com/secapi/pay/refund',
        'refund_query' => 'https://api.mch.weixin.qq.com/pay/refundquery',
        'download' => 'https://api.mch.weixin.qq.com/pay/downloadbill',
    ];
    // 支付pay信息
    private static $shop = [
//        'appid' => '', // 微信支付分配的公众账号ID（企业号corpid即为此appId）
//        'mch_id' => '', // 微信支付分配的商户号
//        'password' => '', // 支付密码
//        'ssl_cert' => '',  // 证书地址
//        'ssl_key' => '',  // 证书地址
    ];
    // 异步通知地址
    private static $notify_url = '';
    // 错误信息
    private static $error_msg = '';

    /**
     * 初始化商家信息
     * @param mixed $shop
     * @param none
     * @return none
     */
    private static function initShop($shop=[])
    {
        // 若为空，则默认查询数据库数据
        if (empty($shop))
        {
            empty(self::$shop) && self::$shop = AppModel::get(1, false);
        }
        elseif (is_numeric($shop))
        {
            empty(self::$shop) && self::$shop = AppModel::get($shop, false);
        }
        else
        {
            // 合并数组，从而达到替换效果
            self::$shop = array_merge(self::$shop, $shop);
        }
        empty(self::$shop) && die('[Wecaht_Pay] : error $shop id');
    }

    /**
     * 网页Web支付
     * @param array $order
     * @param array $shop
     * @return mixed
     */
    public static function webPay(Array $order=[], $shop=[])
    {
        // 初始化商家信息
        self::initShop($shop);
        // 根据订单信息获取prepay_id
        $prepay_id = self::getPrepayId($order);
        // 判断是否请求成功
        if (empty($prepay_id))
        {
            return false;
        }
        // 获取成功，则进行支付参数准备
        $data = [
            'appId' => self::$shop['appid'],
            'timeStamp' => time().'', // 需要为字符串
            'nonceStr' => self::nonceStr(),
            'package' => 'prepay_id='.$prepay_id,
            'singType' => 'MD5'
        ];
        // 进行数据签名
        $data['paySign'] = self::makeSign($data);
        // 返回数据
        return $data;
    }

    /**
     * 请求下单接口
     * @param array $order
     * @param none
     * @return array
     */
    private static function getPrepayId(Array $order=[])
    {
        // 定义参数
        $param = [
            'appid' => self::$shop['appid'], // 微信支付分配的公众账号ID（企业号corpid即为此appId）
            'mch_id' => self::$shop['mch_id'], // 微信支付分配的商户号
            // 'device_info' => '', // 自定义参数，可以为终端设备号(门店号或收银设备ID)，PC网页或公众号内支付可以传"WEB"
            'nonce_str' => self::nonceStr(32), // 随机字符串，长度要求在32位以内。推荐随机数生成算法
            'sign_type' => 'MD5', // 签名类型，默认为MD5，支持HMAC-SHA256和MD5。
            'body' => $order['body'], // 商品简单描述，该字段请按照规范传递，具体请见参数规定
            // 'detail' => '', // 单品优惠字段(暂未上线)
            // 'attach' => '', // 附加数据，在查询API和支付通知中原样返回，可作为自定义参数使用。
            'out_trade_no' => $order['id'], // 商户系统内部订单号，要求32个字符内，只能是数字、大小写字母_-|*@ ，且在同一个商户号下唯一。
            'fee_type' => 'CNY', // 符合ISO 4217标准的三位字母代码，默认人民币：CNY
            'total_fee' => $order['money'] * 100, // 订单总金额，单位为分
            // 'spbill_create_ip' => '', // APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。
            'notify_url' => self::$notify_url, // 异步接收微信支付结果通知的回调地址，通知url必须为外网可访问的url，不能携带参数。
            'trade_type' => 'JSAPI', // JSAPI--公众号支付、NATIVE--原生扫码支付、APP--app支付
            // 'limit_pay' => 'no_credit', // 上传此参数no_credit--可限制用户不能使用信用卡支付
            'openid' => $order['openid'], // trade_type=JSAPI时（即公众号支付），此参数必传，此参数为微信用户在商户对应appid下的唯一标识。
            'sign' => '', // 通过签名算法计算得出的签名值，详见签名生成算法
        ];
        // 处理参数

        // 生成xml
        $xml = \Xml::make($param);
        // 进行数据请求
        $result = self::httpPost(self::$order['make'], $xml);
        // 判断请求结果
        if (is_array($result))
        {
            // 验证数据有效性，签名验证

            // 返回结果
            return $result['prepay_id'];
        }
        // 返回false
        return false;
    }

    /**
     * 获取错误信息
     * @param none
     * @param none
     * @return string
     */
    public static function getErrorMsg()
    {
        // 返回错误信息
        return self::$error_msg;
    }

    /**
     * 根据参数制作加密签名
     * @param array $data
     * @param string $key
     * @return string
     */
    private static function makeSign(Array $data=[], $key='')
    {
        // 若key为空，直接使用商户密码
        empty($key) && $key = self::$shop['password'];
        // 字典序排序
        ksort($data);
        // 拼接成 参数名称=值&参数名称=值
        $str = http_build_query($data);
        // 拼接KEY
        $str .= '&key='.$key;
        // MD5加密，并大写
        return strtoupper(md5($str));
    }

    /**
     * URL访问请求，进行POST请求
     * @param string $url
     * @param mixed $data
     * @param array $cert[cert] $cert[key]
     * @return mixed
     */
    private static function httpPost($url='', $data=[], $cert=[])
    {
        is_array($data) && $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * 随机生成乱码
     * @param int $length
     * @return string $key
     */
    private static function nonceStr($length=32)
    {
        $key = '';
        $str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for($i = 0; $i < $length; $i++)
        {
            $key .= $str[mt_rand(0,61)];
        }
        return $key;
    }
}