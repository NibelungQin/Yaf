<?php
namespace Aliyun\Mns\Responses;

use Aliyun\Mns\Constants;
use Aliyun\Mns\Exception\MnsException;
use Aliyun\Mns\Exception\QueueNotExistException;
use Aliyun\Mns\Exception\MessageNotExistException;
use Aliyun\Mns\Responses\BaseResponse;
use Aliyun\Mns\Common\XMLParser;
use Aliyun\Mns\Traits\MessagePropertiesForPeek;

class PeekMessageResponse extends BaseResponse
{
    use MessagePropertiesForPeek;

    // boolean, whether the message body will be decoded as base64
    private $base64;

    public function __construct($base64 = TRUE)
    {
        $this->base64 = $base64;
    }

    public function setBase64($base64)
    {
        $this->base64 = $base64;
    }

    public function isBase64()
    {
        return ($this->base64 == TRUE);
    }

    public function parseResponse($statusCode, $content)
    {
        $this->statusCode = $statusCode;
        if ($statusCode == 200) {
            $this->succeed = TRUE;
        } else {
            $this->parseErrorResponse($statusCode, $content);
        }

        $xmlReader = $this->loadXmlContent($content);

        try {
            $this->readMessagePropertiesForPeekXML($xmlReader, $this->base64);
        } catch (\Exception $e) {
            throw new MnsException($statusCode, $e->getMessage(), $e);
        } catch (\Throwable $t) {
            throw new MnsException($statusCode, $t->getMessage());
        }

    }

    public function parseErrorResponse($statusCode, $content, MnsException $exception = NULL)
    {
        $this->succeed = FALSE;
        $xmlReader = $this->loadXmlContent($content);

        try {
            $result = XMLParser::parseNormalError($xmlReader);
            if ($result['Code'] == Constants::QUEUE_NOT_EXIST)
            {
                throw new QueueNotExistException($statusCode, $result['Message'], $exception, $result['Code'], $result['RequestId'], $result['HostId']);
            }
            if ($result['Code'] == Constants::MESSAGE_NOT_EXIST)
            {
                throw new MessageNotExistException($statusCode, $result['Message'], $exception, $result['Code'], $result['RequestId'], $result['HostId']);
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
}

?>
