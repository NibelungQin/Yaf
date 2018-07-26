<?php
namespace Aliyun\Mns\Requests;

use Aliyun\Mns\Requests\BaseRequest;

class GetAccountAttributesRequest extends BaseRequest
{
    public function __construct()
    {
        parent::__construct('get', '/?accountmeta=true');
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
