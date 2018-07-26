<?php
/**
 * 文件上传类(bate 1.0)
 * User: Marico
 * Date: 16/8/9
 * Time: 11:50
 */
use Aliyun\Oss;
use System\FileModel;
class OssUpload
{
    // 配置信息
    private static $config = [
        'maxSize'       =>  -1,    // 上传文件的最大值
        'allowTypes'    =>  ['image/jpeg', 'image/png'], // 允许上传的类型
        'allowExts'     =>  ['gif','jpg','jpeg','bmp','png','swf','xls','xlsx','doc','docx','mp3','mp4'], // 允许上传的后缀
        //'basePath'      =>  APP_PATH . '/public', // 保存绝对目录
        'folderFormat'  =>  'Y-m-d', // 文件夹保存格式(按天保存)
        'thumb'         =>  false, // 是否压缩图片
        'thumb_ext'     =>  ['jpg', 'jpeg'],
        'percent'       =>  0.5, // 压缩比例
        'quality'       =>  75 // 压缩质量 100为最佳,0为最差
    ];
    // 需要操作的Model对象
    private static $Model = 'FileModel';
    // 操作用户编号和类型
    public static $user = [
        'user_id' 		=> 0,
        'user_type' 	=> 0,
        'category_id' 	=> 1,
        'aid'			=>0,
    ];

    /**
     * 修改配置
     * @param string $key
     * @param string $value
     * @return none
     */
    public static function config($key='', $value='')
    {
        self::$config[$key] = $value;
    }

    
    /**
     * 上传文件
     * @param string $folder
     * @param string $bucket
     * @param string $thumb  是否压缩
     * @param string $repeat 允许重复上传
     * @return none
     */
    public static function file($folder='', $bucket='',$thumb=true,$repeat=false)
    {
		// 文件夹处理
        self::$config['basePath'] = APP_PATH . '/public';
        $folder = empty($folder)?'uploads/':ltrim($folder, '/');
        $folder = '/' . rtrim($folder, '/').'/';
        $folder .= date(self::$config['folderFormat']).'/';
        // 返回数据
        $data = [];
        // 获取超全局变量内的文件
		
        $files = self::getFiles($_FILES);
		
        // 数据验证检查
        $result = self::verify($files);
        
      
        if ($result !== true)
        {
            return ['status'=>false, 'info'=>$result];
        }
        // 按照hash检查上传文件,($files传址)
        if(!$repeat){
        	$data = self::hashCheck($files);
        }
        // 判断是否需要压缩图片
        if ($thumb && self::$config['thumb'])
        {
            $result = self::reduce($files);
            if ($result !== true)
            {
                return ['status'=>false, 'info'=>$result];
            }
        }
        // 判断是否上传至OSS中
        if (empty($bucket))
        {
            $files = self::local($files, $folder);
        }
        else
        {
            $files = self::oss($files, $folder, $bucket);
        }
        
       
        // 保存至数据库
        foreach ($files as &$file)
        {
            self::saveFile($file);
        }
        // 若返回数据非数组,则证明有文件上传失败,返回错误信息
        if (!is_array($files))
        {
            return ['status'=>false, 'info'=>$result];
        }
        // 返回数据
        return [
            'status' => true,
            'info' => array_merge($data, $files)
        ];
    }
    /**
	*
	* @param $url:需要授权URL  必须填写
	* @param $bucket：OSS 桶， 如果没有填写 直接到url中获取
	* @return array['status','info']   status=1 成功返回 0失败返回  info:为授权后的url  或者为错误消息
     */
    public static function signUrl($url,$bucket='',$timeout =120)
    {	
    	if(empty($bucket)){
    		$bucket=str_replace("http://", '',trim($url));
    		$bucket=explode(".", $bucket);
    		$bucket=$bucket[0];
    	}
    	try{
    		$OssClient = Oss::getOssClient($bucket);
    		if (!is_object($OssClient)){return ['status'=>0,'info'=>'无法获取有效的OSS,请检查配置'];}
    		$cfg		=Config::oss($bucket);
    		$base_url	=isset($cfg['httpurl'])? $cfg['httpurl']:'';
    		if($base_url){
    			$object=str_replace($base_url, '', $url);
    			$timeout = 120;
    			/*$options = array(
    			 OssClient::OSS_PROCESS => "image/resize,m_lfit,h_100,w_100" );*/
    			return ['status'=>1,'info'=>$OssClient->signUrl($bucket, $object, $timeout, "GET")];
    		}else{
    			return ['status'=>0,'info'=>''];
    		}
    	}catch (\Exception $e){
    		return ['status'=>0,'info'=>$e->getMessage()];
    	}
    	
    }
    /**
     * 图片base64位处理
     * @param [string] $base64 [图片base64编码]
     * @param [string] $folder [文件夹名称]
     * @param [string] $bucket [oss库名称]
     * @return [bool/string] 图片上传路径
     */
    public static function base64_img($base64='', $folder = 'base64', $bucket='')
    {
        // 文件夹处理
        self::$config['basePath'] = APP_PATH . '/public';
        $folder = empty($folder)?'uploads/':ltrim($folder, '/');
        $folder = '/' . rtrim($folder, '/').'/';
        $folder .= date(self::$config['folderFormat']).'/';
        // $base64参数处理
        $base64 = substr(strstr($base64, ','),1);
        $base64 = base64_decode($base64);
        if (empty($base64))
        {
            return ['status'=>false, 'info'=>'BASE64解码失败'];
        }
        // 生成唯一名称
        $name = uniqid().'.jpg';
        // 判断是否上传至OSS
        if (!empty($bucket))
        {
            // 获取OSS客户端对象
            $OssClient = Oss::getOssClient($bucket);
            // 删除左侧的/
            $path = ltrim($folder, '/').$name;
            // 将文件上传至OSS
            $OssClient->putObject($bucket, $path, $base64);

            return [
                'status' => true,
                'info' => '文件保存成功',
                'path' => Oss::getHttpUrl($bucket).$path,
            ];
        }
        else
        {
            // 获取保存路径
            $savePath = self::$config['basePath'].$folder;
            // 检查路径权限
            $result = self::checkFolder($savePath);
            if ($result !== true)
            {
                return ['status'=>false, 'info'=>$result];
            }
            // 检查文件是否重复
            if (is_file($savePath.$name))
            {
                return ['status'=>false, 'info'=>'文件唯一名称生成失败'];
            }
            // 保存文件,判断是否保存成功
            if(!file_put_contents($savePath.$name, $base64))
            {
                return ['status'=>false, 'info'=>'文件保存失败'];
            }
            // 返回文件访问路径
            return  [
                'status' => true,
                'info' => '文件保存成功',
                'path' => $folder.$name,
            ];
        }
    }

    /**
     * 已知文件，处理上传至指定位置(配合微信上传)
     * @param array $files
     * @param string $folder
     * @param string $bucket
     * @return array
     */
    public static function wechatFile($files=[], $folder='', $bucket='')
    {
        // 文件夹处理
        self::$config['basePath'] = APP_PATH . '/public';
        $folder = empty($folder)?'uploads/':ltrim($folder, '/');
        $folder = '/' . rtrim($folder, '/').'/';
        $folder .= date(self::$config['folderFormat']).'/';
        // 返回数据
        $data = [];
        // 判断是否需要压缩图片
        if (self::$config['thumb'])
        {
            $result = self::reduce($files);
            if ($result !== true)
            {
                return ['status'=>false, 'info'=>$result];
            }
        }
        // 判断是否上传至OSS中
        if (empty($bucket))
        {
            $files = self::local($files, $folder);
        }
        else
        {
            $files = self::oss($files, $folder, $bucket);
        }
        // 保存至数据库
        foreach ($files as &$file)
        {
            self::saveFile($file);
        }
        // 若返回数据非数组,则证明有文件上传失败,返回错误信息
        if (!is_array($files))
        {
            return ['status'=>false, 'info'=>$result];
        }
        // 返回数据
        return [
            'status' => true,
            'info' => array_merge($data, $files)
        ];
    }

    /**
     * 上传至OSS
     * @param array $files
     * @param string $folder
     * @param string $bucket
     * @return none
     */
    private static function oss($files=[], $folder='', $bucket='')
    {
        // 删除最左侧/
        $folder = ltrim($folder, '/');
        // 获取OSS客户端Object
        $OssClient = Oss::getOssClient($bucket);
        if (!is_object($OssClient))
        {
            return '无法获取有效的OSSClient,请检查配置';
        }
        // 循环处理文件
        foreach ($files as $key => &$file)
        {
            // 制造唯一名称
            $name = uniqid().'.'.$file['ext'];
            // 上传至OSS
            $OssClient->uploadFile($bucket, $folder.$name, $file['tmp_name']);
            // 上传完成后,删除临时文件
            @unlink($file['tmp_name']);
            unset($file['tmp_name']);
            // 获取OSS访问路径
            $file['path'] = Oss::getHttpUrl($bucket).$folder.$name;
        }
        return $files;
    }

    /**
     * 上传至本地
     * @param array $files
     * @param string $folder
     * @return none
     */
    private static function local($files=[], $folder='')
    {
        // 获取设置信息
        $config = self::$config;
        // 获取保存文件路径
        $saveFolder = $config['basePath'].$folder;
        // 检查文件夹权限
        $result = self::checkFolder($saveFolder);
        if ($result !== true)
        {
            return $result;
        }
        // 循环处理文件
        foreach ($files as $key => &$file)
        {
            // 制造唯一名称
            $name = uniqid().'.'.$file['ext'];
            if (is_file($saveFolder.$name))
            {
                return $file['name'].'生成文件唯一名称失败';
            }
            // 移动文件,判断是否移动成功
            if (!move_uploaded_file($file['tmp_name'], $saveFolder.$name))
            {
                return $file['name'].'文件保存失败';
            }
            // 删除键值
            unset($file['tmp_name']);
            // 文件存储路径
            $file['path'] = $folder.$name;
        }
        return $files;
    }

    /**
     * 图片进行压缩处理()
     * @param [array] $files [图片数组]
     * @param [string] $prefix [压缩图前缀]
     * @return none
     */
    private static function reduce(&$files=[])
    {
        $percent = self::$config['percent']; // 压缩比例
        $quality = self::$config['quality']; // 图片质量

        foreach ($files as &$file)
        {
            // 判断图片是否在可压缩之列
            if (!in_array($file['ext'], self::$config['thumb_ext']))
            {
                continue;
            }
            $fileName = $file['tmp_name'];
            // 获取新的尺寸
            list($width, $height) = getimagesize($fileName);
            $new_width = $width * $percent;
            $new_height = $height * $percent;
            // 重新取样
            $image_p = imagecreatetruecolor($new_width, $new_height);
            $image = imagecreatefromjpeg($fileName);
            imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            // 输出
            $result = imagejpeg($image_p, $fileName, $quality);
            // 重新计算图片大小
            $file['size'] = filesize($fileName);
            if ($result == false)
            {
                return $file['name'].'缩略图生成失败';
            }
        }
        return true;
    }

    /**
     * 保存文件信息至数据库
     * @param array $file
     * @return array $data 已存在文件
     */
    private static function saveFile(&$file=[])
    {
        unset($file['error']);
        // 保存文件至数据库
        $file['user_id'] 	=empty(self::$user['user_id']) ? 0: intval(self::$user['user_id']);
        $file['user_type'] 	=empty(self::$user['user_type']) ? 0: intval(self::$user['user_type']); 
        if(self::$user['aid']>0){
        	//ljw 为了保证兼容
        	$file['aid'] 		=self::$user['aid'];
        }
        $file['create_time']=time();
        $file['update_time']=$file['create_time'];
      
        $id = FileModel::insertGetId($file);

        if (!empty($id))
        {
            $file['id'] = $id;
        }
    }

    /**
     * 根据hash检查文件是否已存在
     * @param array $files
     * @return array $data 已存在文件
     */
    private static function hashCheck(&$files=[])
    {
        // 获取所有文件的hash值
        $hashs = array_column($files, 'hash');
        // 按照hash查找数据库
        $data = FileModel::have_files($hashs);
        // 若数据库中没有重复的内容
        if (empty($data))
        {
            return [];
        }
        // 获取数据库中已有hash
        $hashs = array_column($data, 'hash');
        // 循环,剥离已上传的文件
        foreach ($files as $key => $file)
        {
            if (in_array($file['hash'], $hashs))
            {
                // 删除文件
                @unlink($file['tmp_name']);
                unset($files[$key]);
            }
        }
        return $data;
    }

    /**
     * 文件类型判断,大小判断
     * @param $files
     * @return bool/string;
     */
    private static function verify($files=[])
    {
        if (empty($files))
        {
            return '没有任何上传文件';
        }
        // 获取设置信息
        $config = self::$config;

        // 循环所有上传文件
        foreach ($files as $file)
        {
            // 捕获错误代码
            if ($file['error'] !== 0)
            {
                return $file['name'].self::error($file['error']);
            }
            // 判断上传文件大小
            if ($config['maxSize'] != -1
                && $file['size'] > $config['maxSize'])
            {
                return $file['name'].'-上传文件过大';
            }
            // 判断文件类型
            if (!in_array(strtolower($file['type']), $config['allowTypes']))
            {
                return $file['name'].'-MIME类型不允许';
            }
            // 判断文件后缀
            if (!in_array(strtolower($file['ext']), $config['allowExts']))
            {
                return $file['name'].'-文件类型不允许';
            }
            // 判断文件是否通过POST提交
            if (!is_uploaded_file($file['tmp_name']))
            {
                return $file['name'].'-为非法上传文件';
            }
        }
        // 清除读取文件的缓存
        clearstatcache();

        return true;
    }

    /**
     * 检查目录文件
     * @param $saveFolder
     * @return bool|string
     */
    private static function checkFolder($saveFolder)
    {
        // 判断文件夹是否存在,不存在则创建,创建失败则返回错误
        if ( !is_dir($saveFolder)
            && !mkdir($saveFolder, 0777, true)
        )
        {
            return '上传目录'.$saveFolder.'不存在';
        }
        // 判断目录是否拥有写权限
        if (!is_writeable($saveFolder))
        {
            return '上传目录'.$saveFolder.'不可写';
        }

        return true;
    }

    /**
     * 转换上传文件数组变量为正确的方式
     * @access private
     * @param array $files  上传的文件变量
     * @return array
     */
    private static function getFiles($_files, &$fileArray=[])
    {
    	//print_r($_files);die();
        foreach ($_files as $files)
        {
            if (is_array($files['name']))
            {
                foreach ($files['name'] as $key => $value)
                {
                    $info = pathinfo($value);

                    $temp = [
                        'name' => $value,
                        'error' => $files['error'][$key],
                        'type' => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'ext' => $info['extension'],
                        'hash' => md5_file($files['tmp_name'][$key]),
                        'size' => $files['size'][$key]
                    ];

                    array_push($fileArray, $temp);
                }
            }
            else
            {
                $info = pathinfo($files['name']);

                $files['ext'] = $info['extension'];

                $files['hash'] = md5_file($files['tmp_name']);

                array_push($fileArray, $files);
            }
        }
        return $fileArray;
    }

    /**
     * 获取错误代码信息
     * @access public
     * @param string $errorNo  错误号码
     * @return void
     */
    private function error($errorNo)
    {
        switch($errorNo)
        {
            case 1:
                $error = '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值';
                break;
            case 2:
                $error = '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值';
                break;
            case 3:
                $error = '文件只有部分被上传';
                break;
            case 4:
                $error = '没有文件被上传';
                break;
            case 6:
                $error = '找不到临时文件夹';
                break;
            case 7:
                $error = '文件写入失败';
                break;
            default:
                $error = '未知上传错误！';
        }
        return $error;
    }
}