<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/4/9
 * Time: 下午8:45
 */
namespace Wechat;

class Media
{
    // 请求url地址
    private static $url = [
        'get_count' => 'https://api.weixin.qq.com/cgi-bin/material/get_materialcount', // 统计接口
        'get_list' => 'https://api.weixin.qq.com/cgi-bin/material/batchget_material', // 列表接口
        'add_material' => 'https://api.weixin.qq.com/cgi-bin/material/add_material', // 永久素材
        'add_media' => 'https://api.weixin.qq.com/cgi-bin/media/upload', // 临时素材
        'get_media' => 'https://api.weixin.qq.com/cgi-bin/media/get', // 获取临时素材接口
        'get_material' => 'https://api.weixin.qq.com/cgi-bin/material/get_material', // 获取永久素材
        'del_material' => 'https://api.weixin.qq.com/cgi-bin/material/del_material', // 删除永久素材
        'add_news' => 'https://api.weixin.qq.com/cgi-bin/material/add_news', // 永久图文素材新增
        'uploadimg' => 'https://api.weixin.qq.com/cgi-bin/media/uploadimg',
        'update_news' => 'https://api.weixin.qq.com/cgi-bin/material/update_news', // 永久图文素材修改
    ];
    // 定义type对应的ID
    private static $type = [
        'image' => 1,
        'news' => 2,
        'voice' => 3,
        'video' => 4,
        'thumb' => 5
    ];

    /**
     * 根据文件后缀，获取上传类型
     * @param string $ext
     * @param none
     * @return int
     */
    public static function extToType($ext='')
    {
        // 判断是否为图片
        if (in_array($ext, ['jpg', 'jpge', 'gif', 'png']))
        {
            return 'image';
        }
        // 判断是否为视频
        if (in_array($ext, ['mp4']))
        {
            return 'video';
        }
        // 判断是否为录音
        if (in_array($ext, ['mp3', 'amr']))
        {
            return 'voice';
        }
        return false;
    }

    /**
     * 获取media类型所对应编号
     * @param string $type
     * @param none
     * @return int
     */
    public static function getTypeId($type='')
    {
        return isset(self::$type[$type]) ? self::$type[$type] : 0;
    }

    /**
     * 获取ID=>类型
     * @param none
     * @param none
     * @return int
     */
    public static function getTypeSelect()
    {
        return array_flip(self::$type);
    }

    /**
     * 获取资源总数
     * @param array $public
     * @return mixed
     */
    public static function getCount($public=[])
    {
        // 请求地址
        $url = self::makeUrl('get_count', $public);
        // 进行数据请求,并返回结果
        return self::httpGet($url);
    }

    /**
     * 获取资源列表
     * @param array $public
     * @param string $type
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public static function getList($public=[], $type='', $page=1, $limit=20)
    {
        // 请求地址
        $url = self::makeUrl('get_list', $public);
        // 请求参数
        $data = [
            'type' => $type,
            'offset' => ($page-1)*$limit,
            'count' => $limit,
        ];
        // 进行数据请求,并返回结果
        return self::httpPost($url, $data);
    }

    /**
     * 上传文件
     * @param array $public
     * @param array $files
     * @param bool $is_temp
     * @return array|bool
     */
    public static function uploadMedia($public=[], $files=[], $is_temp=false)
    {
        // 定义变量
        $data = [];
        // 获取public_id
        is_numeric($public) && $public_id = $public;
        is_array($public) && isset($public['id']) && $public_id = $public['id'];
        // 获取上传的文件
        $files = empty($files) ? self::getFiles() : $files;
        // 判断是否为临时素材，决定上传情况
        $url = $is_temp ? self::makeUrl('add_media', $public) : self::makeUrl('add_material', $public);
        // 文件进行上传
        foreach ($files as $key => $value)
        {
            // 根据后缀获取类型
            $type = self::extToType($value['ext']);
            // 判断后缀是否允许，不允许则跳过
            if (empty($type)) {continue;}
            // 准备参数
            $postUrl = $url.'&type='.$type;
            $param = [
                'media' => new \CURLFile($value['tmp_name'], $value['type'], $value['name']),
            ];
            // 上传并获取结果
            $result = self::httpPost($postUrl, $param);
            if (!isset($result['media_id'])) {continue;}
            // 修正数据
            isset($result['created_at']) || $result['created_at'] = time();
            // 追加 $data array
            array_push($data, [
                'create_time' => $result['created_at'],
                'update_time' => $result['created_at'],
                'status' => 1,
                'expires_in' => $is_temp?$result['created_at']+259200:0,
                'media_id' => $result['media_id'],
                'name' => $value['name'],
                'url' => isset($result['url']) ? $result['url'] : '',
                'type' => self::getTypeId($type),
                'public_id' => $public_id,
            ]);
        }
        return $data;
    }

    /**
     * 删除永久素材
     * @param array $public
     * @param string $media_id
     * @return json
     */
    public static function delMaterial($public=[], $media_id='')
    {
        // 请求地址
        $url = self::makeUrl('del_material', $public);
        // 请求参数
        $data = [
            'media_id' => $media_id,
        ];
        // 进行数据请求,并返回结果
        return self::httpPost($url, $data);
    }

    /**
     * 制作请求URL地址
     * @param string $urlKey
     * @param array $public
     * @return string
     */
    private static function makeUrl($urlKey='', $public=[])
    {
        return self::$url[$urlKey].'?access_token='.self::getAccessToken($public);
    }

    /**
     * 获取access_token
     * @param array $public
     * @return string
     */
    private static function getAccessToken($public=[])
    {
        return Open::publicAccessToken($public);
    }

    /**
     * 获取所有上传的文件
     * @access private
     * @param none
     * @return array
     */
    private static function getFiles()
    {
        // 定义变量
        $fileArray = [];
        $_files = $_FILES;
        foreach ($_files as $files)
        {
            if (is_array($files['name']))
            {
                foreach ($files['name'] as $key => $value)
                {
                    $info = pathinfo($value);
                    array_push($fileArray, [
                        'name' => $value,
                        'error' => $files['error'][$key],
                        'type' => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'ext' => $info['extension'],
                        'size' => $files['size'][$key]
                    ]);
                }
            }
            else
            {
                $info = pathinfo($files['name']);
                $files['ext'] = $info['extension'];
                array_push($fileArray, $files);
            }
        }
        return $fileArray;
    }

    /**
     * URL访问请求，进行GET请求
     * @param string $url 目标地址
     * @param none
     * @return $resutl 请求返回结果
     */
    private static function httpGet($url='')
    {
        $ch = curl_init ();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }

    /**
     * URL访问请求，进行POST请求
     * @param string $url
     * @param array $data
     * @param none
     * @return mixed
     */
    private static function httpPost($url='', $data=[])
    {
        // 若data为array
        if (is_array($data))
        {
            // 判断是否需要进行json_encode操作
            $need_json = true;
            foreach ($data as $v)
            {
                if ($v instanceof \CURLFile)
                {
                    $need_json = false;
                    break;
                }
            }
            $need_json && $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        $ch = curl_init ();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        curl_close ($ch);
        return json_decode($result, true);
    }
}