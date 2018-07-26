<?php
namespace Aliyun\Mns\Requests;

use Aliyun\Mns\Constants;
use Aliyun\Mns\Requests\BaseRequest;
use Aliyun\Mns\Model\TopicAttributes;

class DeleteTopicRequest extends BaseRequest
{
    private $topicName;

    public function __construct($topicName)
    {
        parent::__construct('delete', 'topics/' . $topicName);
        $this->topicName = $topicName;
    }

    public function getTopicName()
    {
        return $this->topicName;
    }

    public function generateBody()
    {
        return NULL;
    }

    public function generateQueryString()
    {
        return NULL;
    }
}
?>
