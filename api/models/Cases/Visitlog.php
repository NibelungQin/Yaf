<?php
//访问日志
namespace Cases;
class VisitlogModel extends \Model
{
    // 数据库主键
    protected $pk = 'id';
    // 数据表名称(不含前缀)
    protected $name = 'visit_log';
    // 数据表前缀
    protected $prefix = 'case_';
    //
    protected $autoWriteTimestamp = false;
    /***
    *返回数据列表
    *@$where  查询条件
    *@$order  排序
    *$page    每页多少行数据
    *$size    分页
    */
    public static function  getList($where=[],$field,$page=1, $count=5,$order='id desc'){
    	$data= self::where($where)->field($field)->page($page, $count)->order($order)->asArray()->select();
    	return $data;
    } 
	/**
	$wid 微信ID
	$ctype 案例分类
	$actid 案例信息
	 */
    public static function Log($ctype=0,$actid=0){
    	
    	$data['server_info']	=json_encode($_SERVER);
    	$data['ip']				=self::getip();
    	$data['wid']			=isset( $_SESSION['wid'] ) ? $_SESSION['wid'] :0;
    	$data['ctype']			=$ctype;
    	$data['aid']			=$actid;
    	$data['create_time']	=time();
    	 
    	return self::create($data);
    }
    
    private static function getip(){
    	if(getenv('HTTP_CLIENT_IP')){
    		$onlineip = getenv('HTTP_CLIENT_IP');
    	}
    	elseif(getenv('HTTP_X_FORWARDED_FOR')){
    		$onlineip = getenv('HTTP_X_FORWARDED_FOR');
    	}
    	elseif(getenv('REMOTE_ADDR')){
    		$onlineip = getenv('REMOTE_ADDR');
    	}
    	else{
    		$onlineip = $HTTP_SERVER_VARS['REMOTE_ADDR'];
    	}
    	return $onlineip;
    }
}