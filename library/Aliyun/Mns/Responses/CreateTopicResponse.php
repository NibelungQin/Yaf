<?php
namespace Aliyun\Mns\Responses;

use Aliyun\Mns\Constants;
use Aliyun\Mns\Exception\MnsException;
use Aliyun\Mns\Exception\TopicAlreadyExistException;
use Aliyun\Mns\Exception\InvalidArgumentException;
use Aliyun\Mns\Responses\BaseResponse;
use Aliyun\Mns\Common\XMLParser;

class CreateTopicResponse extends BaseResponse
{
    private $topicName;

    public function __construct($topicName)
    {
        $this->topicName = $topicName;
    }

    public function parseResponse($statusCode, $content)
    {
        $this->statusCode = $statusCode;
        if ($statusCode == 201 || $statusCode == 204) {
            $this->succeed = TRUE;
        } else {
            $this->parseErrorResponse($statusCode, $content);
        }
    }

    public function parseErrorResponse($statusCode, $content, MnsException $exception = NULL)
    {
        $this->succeed = FALSE;
        $xmlReader = $this->loadXmlContent($content);

        try {
            $result = XMLParser::parseNormalError($xmlReader);

            if ($result['Code'] == Constants::INVALID_ARGUMENT)
            {
                throw new InvalidArgumentException($statusCode, $result['Message'], $exception, $result['Code'], $result['RequestId'], $result['HostId']);
            }
            if ($result['Code'] == Constants::TOPIC_ALREADY_EXIST)
            {
                throw new TopicAlreadyExistException($statusCode, $result['Message'], $exception, $result['Code'], $result['RequestId'], $result['HostId']);
            }
            throw new MnsException($statusCode, $result['Message'], $exception, $result['Code'], $result['RequestId'], $result['HostId']);
        } catch (\Exception $e) {
            if ($exception != NULL) {
                throw $exception;
            } elseif($e instanceof MnsException) {
                throw $e;
            } else {
                throw new MnsException($statusCode, $e->getMessage());
            }
        } catch (\Throwable $t) {
            throw new MnsException($statusCode, $t->getMessage());
        }
    }

    public function getTopicName()
    {
        return $this->topicName;
    }
}

?>
