<?php
namespace Aliyun\Mns\Responses;

use Aliyun\Mns\Constants;
use Aliyun\Mns\Model\TopicAttributes;
use Aliyun\Mns\Exception\MnsException;
use Aliyun\Mns\Exception\TopicNotExistException;
use Aliyun\Mns\Responses\BaseResponse;
use Aliyun\Mns\Common\XMLParser;

class GetTopicAttributeResponse extends BaseResponse
{
    private $attributes;

    public function __construct()
    {
        $this->attributes = NULL;
    }

    public function getTopicAttributes()
    {
        return $this->attributes;
    }

    public function parseResponse($statusCode, $content)
    {
        $this->statusCode = $statusCode;
        if ($statusCode == 200)
        {
            $this->succeed = TRUE;
        }
        else
        {
            $this->parseErrorResponse($statusCode, $content);
        }

        $xmlReader = $this->loadXmlContent($content);

        try {
            $this->attributes = TopicAttributes::fromXML($xmlReader);
        }
        catch (\Exception $e)
        {
            throw new MnsException($statusCode, $e->getMessage(), $e);
        }
        catch (\Throwable $t)
        {
            throw new MnsException($statusCode, $t->getMessage());
        }
    }

    public function parseErrorResponse($statusCode, $content, MnsException $exception = NULL)
    {
        $this->succeed = FALSE;
        $xmlReader = $this->loadXmlContent($content);

        try
        {
            $result = XMLParser::parseNormalError($xmlReader);
            if ($result['Code'] == Constants::TOPIC_NOT_EXIST)
            {
                throw new TopicNotExistException($statusCode, $result['Message'], $exception, $result['Code'], $result['RequestId'], $result['HostId']);
            }
            throw new MnsException($statusCode, $result['Message'], $exception, $result['Code'], $result['RequestId'], $result['HostId']);
        }
        catch (\Exception $e)
        {
            if ($exception != NULL)
            {
                throw $exception;
            }
            elseif ($e instanceof MnsException)
            {
                throw $e;
            }
            else
            {
                throw new MnsException($statusCode, $e->getMessage());
            }
        }
        catch (\Throwable $t)
        {
            throw new MnsException($statusCode, $t->getMessage());
        }
    }
}

?>
