<?php
/**
 * Created by PhpStorm.
 * User: Marico
 * Date: 16/2/23
 * Time: 10:45
 */
use System\FileModel;
use System\ProvinceModel;
use System\CityModel;
use System\ZoneModel;

class Html
{
    /**
     * 将一个数组,按照要求均等分,不够的由空白信息顶上
     * @param [array] none
     * @param []
     * @return [array] $data
     */
    public static function packet_array($data=[], $length=3, $group=3)
    {
        if (empty($data))
        {
            return [];
        }

        $i = 0;
        $arr = [];
        foreach ($data as $k => $v)
        {
            $arr[$i++/$length][] = $v;
        }

        foreach($arr as $k => &$v)
        {
            $res = $length - count($v);
            if ($res > 0 ) {
                $temp = array_slice($data, 0, $res);
                $v = array_merge($v, $temp);
            }
        }
        return array_slice($arr, 0, $group);
    }

    /**
     * 字符截取 支持UTF8/GBK
     * @param string $str
     * @param int $length
     * @param int $start
     * @param string $charset
     * @param bool $suffix
     * @return string
     */
    public static function msubstr($str='', $length=30 , $start=0 , $charset="utf-8", $suffix=true)
    {
        // 去除HTML标签
        $str = strip_tags($str);
        $temp = $str;
        // 若长度未超过，则直接返回
        if (mb_strlen($str) <= $length) {
            return $str;
        }
        // 判断是否存在mb_substr内置函数
        if (function_exists("mb_substr")) {
            $str = mb_substr($str, $start, $length, $charset);
        } elseif (function_exists('iconv_substr')) {
            $str = iconv_substr($str,$start,$length,$charset);
        }
        // 若字符串已处理，则添加...
        if ($temp != $str && $suffix) {
            return $str."…";
        }
        return $str;
    }
    /**
     * @param $cat 要循环的分类
     * @param $pid 父类的id
     * $level 层级
     * $selected 父类属于哪个分类
     * $separator 分隔符
     */
    public static function tree_cat($cat, $pid=0, $selected=0, $level=0, $separator='|-')
    {
        $str = '';
        $temp = '';
        $separation = str_pad($temp,$level*2,$separator,STR_PAD_LEFT );

        foreach($cat as $key => $val)
        {

            if($val['dp_pid']==$pid)
            {

                if($selected==$val['dp_id'])
                {
                    $str.="<option value='".$val['dp_id']."'  selected='selected'><h1>".$separation.$val['title']."</h1></option>";
                }
                else
                {
                    $str.="<option value='".$val['dp_id']."'><h1>".$separation.$val['title']."</h1></option>";
                }
                $pid1 = $val['dp_id'];
                unset($cat[$key]);
                $str.= self::tree_cat($cat,$pid1,$selected,$level+1,$separator);

            }
        }
        return $str;
    }
    /**
     * 分页HTML代码
     * @param int $count 总记录条数
     * @param int $one 一页展示最多行数
     * @param int $page 当前页数
     * @return html
     */
    public static function page($count=0,$one=10,$page=1)
    {
        // 判断是否有必要输出分页HTML
        if ($count < $one) {
            return '';
        }
        // 获取所有参数
        $param = Yaf_Dispatcher::getInstance()->getRequest()->getQuery();
        // 判断是否存在页码
        $page = isset($param['page'])?$param['page']:1;
        // 计算总共需要构建的分页总数
        if ($count % $one == 0) {
            $count = $count/$one ;
        } else {
            $count = $count/$one + 1;
        }
        // 处理总页码
        $count = intval($count);
        // 判断页码是否超过最大页码
        if ($page > $count) {
            $page = $count;
        }
        // 模板
        $template = '<ul class="pagination">';
        // 若页码为1，则禁用翻到前一页
        if ($page == 1) {
            $template .= '<li class="disabled">'
                        .'<a href="javascript:void(0)" aria-label="Previous">'
                        .'<span aria-hidden="true">&laquo;</span>'
                        .'</a></li>';
        } else {
            $param['page'] = $page - 1;
            $template .= '<li>'
                .'<a href="'.Url::router('',$param).'" aria-label="Previous">'
                .'<span aria-hidden="true">&laquo;</span>'
                .'</a></li>';
        }
        // 循环处理
        for ( $i=1;$i<=$count;$i++) {
            if ($i != 1 && $i < $count) {
                // 若开始页码差超过三页
                if ($page-$i == 3) {
                    $template .= '<li class="disabled"><a href="javascript:void(0)">...</a></li>';
                }
                // 若结束页码差超过三页
                if ($count-$i == 1 && $count - $page > 3) {
                    $template .= '<li class="disabled"><a href="javascript:void(0)">...</a></li>';
                }
                // 判断是否朝超出，直接跳过
                if (abs($page-$i) > 2 && $count-$i > 0) {
                    continue;
                }
            }
            // 若为当前页，则选中
            if ($page == $i) {
                $template .= '<li class="active disabled"><a href="javascript:void(0)">'.$i.'</a></li>';
            } else {
                $param['page'] = $i;
                $template .= '<li><a href="'.Url::router('',$param) .'">'.$i.'</a></li>';
            }
        }
        // 判断是否为最后一页
        if ($page == $count) {
            $template .= '<li class="disabled">'
                .'<a href="javascript:void(0)" aria-label="Next">'
                .'<span aria-hidden="true">&raquo;</span>'
                .'</a></li>';
        } else {
            $param['page'] = $page + 1;
            $template .= '<li>'
                .'<a href="'.Url::router('',$param).'" aria-label="Next">'
                .'<span aria-hidden="true">&raquo;</span>'
                .'</a></li>';
        }
        $template .= '</ul>';
        return $template;
    }

    /**
     * 富文本编辑器
     * @param string $name
     * @param string $content
     * @return string
     */
    public static function ueditor($name='content', $content='')
    {
        // 此处有个重名的BUG
        return '<script type="text/html" class="ueditor" ng-model="content_'.mt_rand(1, 500000).'" name="'
                .$name.'">'.(empty($content)?'':htmlspecialchars_decode($content)).'</script>';
    }

    /**
     * 地区联查
     * @param mixed $pid
     * @param mixed $cid
     * @param mixed $zid
     * @return string
     */
    public static function area($pid='', $cid='', $zid='')
    {
        $province_option = '';
        $city_option = '';
        $zone_option = '';

        try
        {
            // 获取所有省份
            $province = ProvinceModel::getSelect([], ['id', 'province']);
            foreach ($province as $k => $v) {
                if ($k == $pid) {
                    $k .= '" selected="selected';
                }
                $province_option .= '<option value="'.$k.'">'.$v.'</option>';
            }
            // 若存在省份ID，则拉去下级城市
            if (!empty($pid) && is_numeric($pid))
            {
                // 获取省份对应的城市ID
                $city = CityModel::getSelect(['province_id' => $pid], ['id', 'city']);
                // 拼接HTML
                foreach ($city as $k => $v) {
                    if ($k == $cid) {
                        $k .= '" selected="selected';
                    }
                    $city_option .= '<option value="'.$k.'">'.$v.'</option>';
                }
            }
            // 若存在城市ID，则拉去下级区县
            if (!empty($cid) && is_numeric($cid))
            {
                $zone = ZoneModel::getSelect(['city_id' => $cid], ['id', 'zone']);
                foreach ($zone as $k => $v) {
                    if ($k == $zid) {
                        $k .= '" selected="selected';
                    }
                    $zone_option .= '<option value="'.$k.'">'.$v.'</option>';
                }
            }
        } catch (Exception $e) {
            // 不做处理
            return $e->getMessage();// 'error select';
        }
        // 更加优化
        $html = '<div class="col-sm-4">'
               .'<select name="province_id" class="form-control input mb15" onchange="parent.area.chooseProvince(this);">'
               .'<option value="">请选择省份</option>'
               .$province_option
               .'</select></div>';
        // 若不需要禁止，则显示
        if ($cid !== false) {
            $html .= '<div class="col-sm-4">'
                .'<select name="city_id" class="form-control input mb15" onchange="parent.area.chooseCity(this);">'
                .'<option value="">请选择城市</option>'
                .$city_option
                .'</select></div>';
        }
        // 若不需要禁止，则显示
        if ($zid !== false) {
            $html .= '<div class="col-sm-4">'
                . '<select name="zone_id" class="form-control input mb15">'
                . '<option value="">请选择区县</option>'
                . $zone_option
                . '</select></div>';
        }
        return $html;
    }

    /**
     * 地图选点HTML样式
     * @param string $lng
     * @param string $lat
     * @return string
     */
    public static function location($lng='', $lat='')
    {
        $html = '<div class="input-group mb15">'
               .'<input type="text" name="longitude" class="form-control longitude" placeholder="请输入经度" value="'.$lng.'">'
               .'<span class="input-group-addon" onclick="parent.box.showMap(this)">地图选择</span>'
               .'<input type="text" name="latitude" class="form-control latitude" placeholder="请输入纬度" value="'.$lat.'">'
               .'</div>';
        return $html;
    }

    /**
     * 上传图片
     * @param string $name
     * @param string $path
     * @param bool $multiple
     * @return string
     */
    public static function uploadImg($name='img', $path='', $multiple=false)
    {
        $html = '<div class="input-group mb15">'
               .'<input type="text" name="'.$name.'" class="form-control" value="'.$path.'">'
               .'<span class="input-group-btn">'
               .' <button type="button" class="btn btn-default" '
               .' onclick="parent.box.showUpload(this,'.($multiple?'true':'false').',true)">上传图片</button>'
               .'</span></div>';

        empty($path)? $path[] = '/admin/images/add_img.png':$path = explode(',', $path);

        foreach ($path as $key => $value) {
            $html .= '<div class="input-group">'
                .'<img src="'.$value.'" class="img-responsive img-thumbnail" width="150px">'
                .'<em class="close" style="position:absolute; top: 0px; right: -14px;" onclick="box.deleteFile(this);">×</em>'
                .'</div>';
        }

        return $html;
    }

    /**
     * 上传图片,最终保存图片编号
     * @param string $name
     * @param string $ids
     * @param bool $multiple
     * @return string
     */
    public static function uploadImgID($name='img', $ids='', $multiple=false)
    {
        $html = '<div class="input-group mb15">'
            .'<input type="text" name="'.$name.'" id="disabledinput" class="form-control" value="'.$ids.'" readonly="readonly">'
            .'<span class="input-group-btn">'
            .' <button type="button" class="btn btn-default" '
            .' onclick="parent.box.showUpload(this, '.($multiple?'true':'false').',false)">上传图片</button>'
            .'</span></div>';

        if (empty($ids)) {
            $files = [
                ['id' => 0, 'path' => '/admin/images/add_img.png'],
            ];
        } else {
            // 获取图片
            $files = FileModel::filesByID($ids);
        }
        // 显示图片
        foreach ($files as $k => $v) {
            $html .= '<div class="input-group">'
                .'<img src="'.$v['path'].'" class="img-responsive img-thumbnail" width="150px">'
                .'<em class="close" style="position:absolute; top: 0px; right: -14px;" '
                .'ids="'.$v['id'].'"'
                .' onclick="box.deleteFile(this);">×</em>'
                .'</div>';
        }
        return $html;
    }

    /**
     * 获取时间
     * @param string $name
     * @param string $value
     * @return string
     */
    public static function setTime($name='time', $value='')
    {
        // 判断值是否为数字，若为数字则转换
        is_numeric($value) && $value = date('Y-m-d H:i:s', $value);
        return '<input type="text" value="'.$value.'" class="form-control" name="'.$name
              .'" onclick="laydate({istime: true, format: \'YYYY-MM-DD hh:mm:ss\'})">';
    }

    /**
     * 获取日期
     * @param string $name
     * @param string $value
     * @return string
     */
    public static function setDate($name='date', $value='')
    {
        // 判断值是否为数字，若为数字则转换
        is_numeric($value) && $value = date('Y-m-d', $value);
        return '<input type="text" value="'.$value.'" class="form-control" name="'.$name
            .'" onclick="laydate({istime: true, format: \'YYYY-MM-DD\'})">';
    }

    /**
     * 选择框选择内容
     * @param array $data
     * @param int $select
     * @param array $keys
     * @return none
     */
    public static function setOption(Array $data=[], $select=-1, $keys=[])
    {
        // 模板
        $template = '';
        // 若keys不为空，则进行处理
        if (!empty($keys))
        {
            // 判断是否有两个字段
            if (count($keys) != 2)
            {
                return '';
            }
            // 循环处理
            foreach($data as $k => $v)
            {
            	$temp=$v[$keys[1]];
            	if(isset($v['dep'])  && $v['dep']>0) {
            		$temp=str_repeat("&nbsp&nbsp", $v['dep']).$temp;
            	}
                $template .= '<option value="'.$v[$keys[0]].'" '
                            . ($v[$keys[0]]==$select?'selected="selected"':'')
                            .'>'.$temp.'</option>';
            }
        }
        else
        {
            // 循环处理
            foreach($data as $k => $v)
            {
                $template .= '<option value="'.$k.'" '
                    . ($k==$select?'selected="selected"':'')
                    .'>'.$v.'</option>';;
            }
        }
        // 返回option html
        return $template;
    }

    /**
     * 选择框
     * @param string $name
     * @param array $data   可选项目
     * @param array/string $select  选中这个项目  1,2,3
     * @return none
     */
    public static function setCheckBox($name='', Array $data=[], $select='')
    {
        // 空数据
        empty($select) || $select = explode(',', $select);
        is_array($select) || $select = [];
        // html
        $template = '';
        // 循环处理
        foreach ($data as $key => $value)
        {
            $template .= "<label><input type='checkbox' name='{$name}' value='{$key}'";
            in_array($key, $select) && $template .=" checked";
            $template .= " />{$value}</label>&nbsp;&nbsp;&nbsp;&nbsp;";
        }
        // 返回option html
        return $template;
    }

    /**
     * 选择框
     * @param string $name
     * @param array $data   可选项目
     * @param string $select  选中这个项目  1
     * @return none
     */
    public static function setRadio($name='',Array $data=[], $select='')
    {
        // html
        $template='';
        // 循环处理
        foreach ($data as $key => $value)
        {
            $template .= "<label><input name='{$name}' type='radio' value='{$key}'";
            $select == $value && $template .= " selected";
            $template .= " />{$value}</label> &nbsp;&nbsp;&nbsp;&nbsp;";
        }
        // 返回option html
        return $template;
    }

    /**
     * 选择框
     * @param string $name
     * @param array $data
     * @param int $select
     * @param array $keys
     * @return none
     */
    public static function setSelect($name='', Array $data=[], $select=-1, $keys=[])
    {
        // 模板
        $template = '<select class="form-control mb15" name="'.$name.'" >';
        $template .= self::setOption($data, $select, $keys);
        $template .= '</select>';
        // 返回option html
        return $template;
    }

    /**  　
     * 调用方式 html::getChild($result,0,$data,1);
     * 递归查找父id为$pid的结点
     * @param array $result  按照父-》子的结构存放查找出来的结点　　返回值
     * @param int $pid  指定的父id
     * @param array $data  被查数据
     * @param int $dep  遍历的深度，初始化为1
     */
    public static function getTree(Array &$result=[], $pid=0, Array &$data=[], $dep=0)
    {
    	// 整体循环构成临时数据 pid -> array
    	$dep == 0 && $data = self::dataToPid($data,'id');
    	foreach ($data as $key=>$value)
    	{
    		// 赋值层次
    		$value['dep'] = $dep;
    		// 数组追加
    		array_push($result, $value);
    		// 若存在子集，则进行递归
    		if (isset($data[$value['id']]))
    		{
    			self::getChild($result, $value['id'], $data, $dep+1);
    		}
    	
    	
    	}
    }
    

    
    /**  　
     * 调用方式 html::getChild($result,0,$data,1);
     * 递归查找父id为$pid的结点
     * @param array $result  按照父-》子的结构存放查找出来的结点　　返回值
     * @param int $pid  指定的父id
     * @param array $data  被查数据
     * @param int $dep  遍历的深度，初始化为1
     */
    public static function getChild(Array &$result=[], $pid=0, Array &$data=[], $dep=0)
    {
    
        // 整体循环构成临时数据 pid -> array
		
    	$dep == 0 && $data = self::dataToPid($data);

         // 判断是否存在此数据，并为数组, // 循环递归
        if (isset($data[$pid]) && is_array($data[$pid]))
        {
        	foreach ($data[$pid] as $key=>$value)
            {	
		        // 赋值层次
                $value['dep'] = $dep;
                // 数组追加
                array_push($result, $value);
               
                // 若存在子集，则进行递归
                if (isset($data[$value['id']]))
                {
                    self::getChild($result, $value['id'], $data, $dep+1);
                }
                
                
            }
        }
    }

    /**
     * 将数据按pid归类
     * @param array $data
     * @param string $pid_key
     * @return array
     */
    public static function dataToPid(Array $data=[], $pid_key='pid')
    {
        // 定义空数组
        $temp = [];
        // 整体循环构成临时数据 pid -> array
        foreach ($data as $v)
        {
        	// 生成键
        	$key = $v[$pid_key];
            // 判断是否已存在键
            isset($temp[$key]) || $temp[$key] = [];
            //追加值
            array_push($temp[$key], $v);
        }
        // 返回结果
        return $temp;
    }
}