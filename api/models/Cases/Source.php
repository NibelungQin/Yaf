<?php
namespace Cases;
class SourceModel extends \Model
{
    // 数据库主键
    protected $pk = 'id';
    // 数据表名称(不含前缀)
    protected $name = 'source';
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

    public static function  getbaseInfo($id){
    	$where				=[];
    	$where['id']		=$id;
    	$data	=self::findOne($where,
    			'title,cover,tips,code_qr');
    	return $data;
    }
    public static function  getInfo($id){
    	$where				=[];
    	$where['id']		=$id;
    	$data	=self::findOne($where,
    			'title,cover,create_time,update_time,tips,content');
    	return $data;
    }
    
}