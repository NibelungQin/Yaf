<?php
//活动模型
namespace Caseadmin;
class ActModel extends \Model
{
    // 数据库主键
    protected $pk = 'id';
    // 数据表名称(不含前缀)party_application
    protected $name = 'act_list';
    // 数据表前缀
    protected $prefix = 'case_';
    //
    protected $autoWriteTimestamp = true;
    
    
    
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
    /***
    *返回数据列表
    *@$where  查询条件
    *@$order  排序
    *$page    每页多少行数据
    *$size    分页
    */
    public static function  getListPage($where=[],$field,$page=1, $count=5,$order='id desc'){
    	$total	=self::where($where)->count();
    	$data	=self::where($where)->field($field)->page($page, $count)->order($order)->asArray()->select();
    	return ['pages'=>['total'=>$total,'pagesize'=>$count],'list'=>$data];
    }
    //获取案例信息
    public static function  getInfo($id){
    	$where				=[];
    	$where['id']		=$id;
    	$data	=self::findOne($where,
    			'id as aid,sid,title,content,url,update_time,active_start_time as start_time ,active_end_time as end_time,cover,clicks,images');
    	if($data){
    		//获取案例的来源信息
    		$_source	=\Cases\SourceModel::getbaseInfo($data['sid']);
    		if($_source){
    			$data['source']=$_source;
    		}
    		return  $data;
    	}else{
    		return false;
    	}
    	
    }
    
}