<?php
//活动模型
namespace Cases;
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
    //获取案例信息
    public static function  getInfo($id){
    	$where				=[];
    	$where['id']		=$id;
    	$where['status']	=1;
    	
    	$data	=self::findOne($where,
    			'isclick,id as aid,sid,cid,title,content,url,update_time,active_start_time as start_time ,active_end_time as end_time,cover,clicks,images');
    	if($data){
    		
    		//获取分类新
    		$_category	=\Cases\CategoryModel::where(['id'=>$data['cid']])->field(['title'])->asArray()->find();
    		if($_category){
    			$data['category']=$_category['title'];
    		}else{
    			$data['category']='未知';
    		}
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