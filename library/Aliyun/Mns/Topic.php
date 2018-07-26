<?php
namespace Aliyun\Mns;

use Aliyun\Mns\Http\HttpClient;
use Aliyun\Mns\AsyncCallback;
use Aliyun\Mns\Model\TopicAttributes;
use Aliyun\Mns\Model\SubscriptionAttributes;
use Aliyun\Mns\Model\UpdateSubscriptionAttributes;
use Aliyun\Mns\Requests\SetTopicAttributeRequest;
use Aliyun\Mns\Responses\SetTopicAttributeResponse;
use Aliyun\Mns\Requests\GetTopicAttributeRequest;
use Aliyun\Mns\Responses\GetTopicAttributeResponse;
use Aliyun\Mns\Requests\PublishMessageRequest;
use Aliyun\Mns\Responses\PublishMessageResponse;
use Aliyun\Mns\Requests\SubscribeRequest;
use Aliyun\Mns\Responses\SubscribeResponse;
use Aliyun\Mns\Requests\UnsubscribeRequest;
use Aliyun\Mns\Responses\UnsubscribeResponse;
use Aliyun\Mns\Requests\GetSubscriptionAttributeRequest;
use Aliyun\Mns\Responses\GetSubscriptionAttributeResponse;
use Aliyun\Mns\Requests\SetSubscriptionAttributeRequest;
use Aliyun\Mns\Responses\SetSubscriptionAttributeResponse;
use Aliyun\Mns\Requests\ListSubscriptionRequest;
use Aliyun\Mns\Responses\ListSubscriptionResponse;

class Topic
{
    private $topicName;
    private $client;

    public function __construct(HttpClient $client, $topicName)
    {
        $this->client = $client;
        $this->topicName = $topicName;
    }

    public function getTopicName()
    {
        return $this->topicName;
    }

    public function setAttribute(TopicAttributes $attributes)
    {
        $request = new SetTopicAttributeRequest($this->topicName, $attributes);
        $response = new SetTopicAttributeResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function getAttribute()
    {
        $request = new GetTopicAttributeRequest($this->topicName);
        $response = new GetTopicAttributeResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function generateQueueEndpoint($queueName)
    {
        return "acs:mns:" . $this->client->getRegion() . ":" . $this->client->getAccountId() . ":queues/" . $queueName;
    }

    public function generateMailEndpoint($mailAddress)
    {
        return "mail:directmail:" . $mailAddress;
    }

    public function generateSmsEndpoint($phone = null)
    {
        if ($phone)
        {
            return "sms:directsms:" . $phone;
        }
        else
        {
            return "sms:directsms:anonymous";
        }
    }

    public function generateBatchSmsEndpoint()
    {
        return "sms:directsms:anonymous";
    }

    public function publishMessage(PublishMessageRequest $request)
    {
        $request->setTopicName($this->topicName);
        $response = new PublishMessageResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function subscribe(SubscriptionAttributes $attributes)
    {
        $attributes->setTopicName($this->topicName);
        $request = new SubscribeRequest($attributes);
        $response = new SubscribeResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function unsubscribe($subscriptionName)
    {
        $request = new UnsubscribeRequest($this->topicName, $subscriptionName);
        $response = new UnsubscribeResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function getSubscriptionAttribute($subscriptionName)
    {
        $request = new GetSubscriptionAttributeRequest($this->topicName, $subscriptionName);
        $response = new GetSubscriptionAttributeResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function setSubscriptionAttribute(UpdateSubscriptionAttributes $attributes)
    {
        $attributes->setTopicName($this->topicName);
        $request = new SetSubscriptionAttributeRequest($attributes);
        $response = new SetSubscriptionAttributeResponse();
        return $this->client->sendRequest($request, $response);
    }

    public function listSubscription($retNum = NULL, $prefix = NULL, $marker = NULL)
    {
        $request = new ListSubscriptionRequest($this->topicName, $retNum, $prefix, $marker);
        $response = new ListSubscriptionResponse();
        return $this->client->sendRequest($request, $response);
    }
}

?>
