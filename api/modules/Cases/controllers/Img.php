<?php
/**
 * 活动模块
 * User: marico
 * Date: 2017/4/11
 * Time: 上午10:36
 */
use Controller\Api;
use Aliyun\Oss;
class ImgController extends Api
{
    // 公众号编号
    protected $aid = 1;													//默认公众号
    /**
    *TODO: Change the autogenerated stub
    */
    public function init()
    {   
    	parent::init();
    }
    public function uploadAction()
    {
    	$base64 = $this->_req->get('uploadimg');
    	if($base64){
    		$res = Upload::base64_img($base64,'case','kfw-mp');
    		if ($res['status'] == true)
    		{
    			$this->ajaxReturn(200, "上传成功", ['path'=>$res['path']]);
    		}
    	}
    	$this->ajaxReturn(2001, "上传失败");
    }
 }