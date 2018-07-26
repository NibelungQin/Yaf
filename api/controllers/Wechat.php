<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/4/1
 * Time: 下午4:13
 */
use Controller\Index;
use Wechat\AppModel;
use Wechat\PublicModel;
use Wechat\Keyword\ReplyModel;

use Wechat\Open;
use Wechat\Message;
use Wechat\Event;

class WechatController extends Index
{
    /**
     * 初始化程序
     * @param none
     * @param none
     */
    public function init()
    {
        parent::init(); // TODO: Change the autogenerated stub
        // 关闭视图渲染
        \Yaf_Dispatcher::getInstance()->disableView();
    }

    /**
     * 获取微信官方发来的ticket消息
     * @param none
     * @param none
     * @return none
     */
    public function ticketAction()
    {    // 获取post提交的xml，转码成array
        $xml = file_get_contents("php://input");
        $data = \Xml::toArray($xml);
        // 判断转码是否成功
        if (is_array($data) && isset($data['appid']))
        {
            // 获取所有get参数
            $get = $this->_req->getQuery();
            $data = array_merge($get, $data);
            // 根据APPID查询
            $app = AppModel::get(['appid' => $data['appid']], false);
            if (!empty($app))
            {
                // 数据解码
                $data = Open::decryptMsg($data, $app);
                Log::debug(1, $data);
                // 判断解码情况
                is_array($data) || die('fail error decryptMsg');
                // 匹配消息信息
                switch ($data['InfoType'])
                {
                    // 更新ticket
                    case 'component_verify_ticket':
                        // 更新APP的ticket
                        $res = AppModel::update([
                            'verify_ticket' => $data['ComponentVerifyTicket'],
                            'ticket_expires' => $data['CreateTime']+600,
                        ], [
                            'id' => $app['id'],
                        ]);
                        empty($res) && die('fail update ticket');
                        break;
                    // 更新授权码
                    case 'updateauthorized':;
                    case 'authorized':
                        // 获取取消更新的PUBLIC APPID
                        $res = PublicModel::update([
                            'auth_code' => $data['AuthorizationCode'],
                            'auth_expires' => $data['AuthorizationCodeExpiredTime'],
                        ], [
                            'appid' => $data['AuthorizerAppid'],
                        ]);
                        empty($res) && die('fail update auth_code');
                        // 首次给予授权码
                        break;
                    case 'unauthorized':
                        // 获取取消更新的PUBLIC APPID
                        $res = PublicModel::update([
                            'status' => 0,
                        ], [
                            'appid' => $data['AuthorizerAppid'],
                        ]);
                        empty($res) && die('fail update ticket');
                        // 取消授权
                        break;
                    default :
                        break;
                }
            }
        }
        die('success');
    }

    /**
     * 获取微信官方发来的消息
     * @param none
     * @param none
     * @return none
     */
    public function messageAction()
    {
        // 获取post提交的xml，转码成array
        $xml = file_get_contents("php://input");
        $data = \Xml::toArray($xml);
        // 插入数据
        Log::debug(4, $data);
        // 判断转码是否成功
        if (is_array($data) && isset($data['tousername']))
        {
            // 获取所有get参数
            $get = $this->_req->getQuery();
            $appid = $this->_req->getParam('appid');
            $data = array_merge($get, $data);
            // 根据APPID查询公众号
            $public = PublicModel::findOne([
                'appid' => $appid
            ], ['id', 'app_id']);
            // 判断是否查询成功
            empty($public) && die('success');
            // 数据解码
            $data = Open::decryptMsg($data, $public['app_id']);
            // 插入日志数据
            Log::debug(3, $data);
            // 判断解码情况
            is_array($data) || die('success');
            // 追加公众号编号
            $data['public_id'] = $public['id'];
            // 一切为了测试
            if ($data['ToUserName'] == 'gh_3c884a361561')
            {
                $this->forTest($data);
            }
            // 判断消息类型，若为event，做event处理
            if ($data['MsgType'] == 'event')
            {
                // 分析事件数据
                $rule_id = Event::analysis($data);
            }
            else
            {
                // 分析关键词数据
                $rule_id = Message::analysis($data);
            }
            // 若返回为Array数组，则处理返回值
            is_numeric($rule_id) || die('success');
            // 获取真正返回的XML数据
            $reply = $this->getReplyContent($rule_id, $data);
            // 判断是否需要回复
            empty($reply) && die('success');
            // 数据加密，输出
            die(Open::encryptMsg($reply, $public['app_id']));
        }
        die('success');
    }

    /**
     * 分析回复情况
     * @param int $rule_id
     * @param array $data
     * @return string
     */
    private function getReplyContent($rule_id=2, Array $data=[])
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
        // 将array数据Xml化
        return \Xml::make(array_merge($data, $reply));
    }

    /**
     * 为了通过全网测试
     * @param Array $data
     * @param none
     * @return xml
     */
    private function forTest(Array $data=[])
    {
        $reply = [];

        // 判断消息类型，若为event，做event处理
        if ($data['MsgType'] == 'event')
        {
            // 准备基础数据
            $reply = [
                'ToUserName' => $data['FromUserName'],
                'FromUserName' => $data['ToUserName'],
                'CreateTime' => time(),
                'MsgType' => 'text',
                'Content' => $data['Event'].'from_callback',
            ];
            // 数据加密，输出
            die(Open::encryptMsg(\Xml::make($reply), 1));
        }
        else if ($data['Content'] == 'TESTCOMPONENT_MSG_TYPE_TEXT')
        {
            $reply = [
                'ToUserName' => $data['FromUserName'],
                'FromUserName' => $data['ToUserName'],
                'CreateTime' => time(),
                'MsgType' => 'text',
                'Content' => 'TESTCOMPONENT_MSG_TYPE_TEXT_callback',
            ];
            // 数据加密，输出
            die(Open::encryptMsg(\Xml::make($reply), 1));
        }
        $auth_code = str_replace('QUERY_AUTH_CODE:', '', $data['Content']);
        // 调用客服接口回复消息
        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=';
        $url .= $this->getAuthorizerAccessToken($auth_code);
        $reply = [
            'touser' => $data['FromUserName'],
            'msgtype' => 'text',
            'text' => [
                'content' => $auth_code.'_from_api',
            ],
        ];
        \Http::post($url, $reply);
        die('success');
    }

    /**
     *
     * @param string $code
     * @param int $id
     * @return string
     */
    private function getAuthorizerAccessToken($code='', $id=1)
    {
        // 访问微信接口，获取数据
        $info = Open::getAuthQuery($code, $id);
        // 判断数据是否获取成功
        if (is_array($info) && isset($info['authorization_info']));
        {
            // 处理数据
            $param = $info['authorization_info'];
            // 获取public信息
            $info = Open::getPublicInfo($param['authorizer_appid'], $id);
            // 判断获取是否成功
            empty($info) && $this->error('获取公众号信息失败');
            // 准备数据库数据
            return $param['authorizer_access_token'];
        }
        return '';
    }
}