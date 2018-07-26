<?php
/**
 * Created by PhpStorm.
 * User: Marico
 * Date: 16/2/15
 * Time: 15:40
 */
class Url
{
    /**
     * 生成跳转链接
     * @param string $point
     * @param string $param
     * @param bool $www
     * @return string
     */
    public static function to($point='', $param='', $www=false)
    {
        $url = '/';
        empty($point) && $point = ACTION;
        $data = explode('/',$point);
        $len = count($data);
        if($len == 1)
        {
            MODULE && MODULE != 'Index' && $url .= MODULE.'/';
            $url .= CONTROLLER.'/';
        }
        else if($len == 2)
        {
            MODULE && MODULE != 'Index' && $url .= MODULE.'/';
        }
        $url .= $point;
        empty($param) || $url .= '?'.http_build_query($param);
        $www === true && $www = 'http://'.$_SERVER['HTTP_HOST'];
        empty($www) || $url = $www.$url;
        return $url;
    }

    /**
     * 生成路由链接
     * @param $point 跳转到 模块/控制器/方法
     * @param $param 跳转带参
     * @param $www 是否加前缀
     * @return string
     */
    public static function router($point='', $param='')
    {
        $url = '/';
        empty($point) && $point = ACTION;
        $data = explode('/',$point);
        $len = count($data);
        if($len == 1)
        {
            MODULE && MODULE != 'Index' && $url .= MODULE.'/';
            $url .= CONTROLLER.'/';
        }
        else if($len == 2)
        {
            MODULE && MODULE != 'Index' && $url .= MODULE.'/';
        }
        $url .= $point;
        empty($param) || $url .= '?s='.str_replace(['=','&'], [';',';'], http_build_query($param));
        return '#'.$url;
    }


    /**
     * 判断是否为手机端访问
     * @param $none
     * @return $bool
     */
    static function is_mobile_request()
    {
        $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';
        $mobile_browser = 0;
        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))
        {
            $mobile_browser++;
        }
        if ((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') !== false))
        {
            $mobile_browser++;
        }
        if(isset($_SERVER['HTTP_X_WAP_PROFILE']))
        {
            $mobile_browser++;
        }
        if(isset($_SERVER['HTTP_PROFILE']))
        {
            $mobile_browser++;
        }

        $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4));

        $mobile_agents = [
            'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
            'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
            'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
            'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
            'newt','noki','oper','palm','pana','pant','phil','play','port','prox',
            'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
            'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
            'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
            'wapr','webc','winw','winw','xda','xda-'
        ];
        if(in_array($mobile_ua, $mobile_agents))
        {
            $mobile_browser++;
        }
        if(strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)
        {
            $mobile_browser++;
        }
        // Pre-final check to reset everything if the user is on Windows
        if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)
        {
            $mobile_browser = 0;
        }
        // But WP7 is also Windows, with a slightly different characteristic
        if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)
        {
            $mobile_browser++;
        }

        return $mobile_browser > 0 ? true : false;
    }

    /**
     * 检查当前URL是否在白名单域
     * @param string $url
     * @param array $domain
     * @return bool
     */
    public static function checkDomain($url='', Array $domain=[])
    {
        // 解析url
        $url = parse_url($url);
        // 判断是否解析出host地址
        if (isset($url['host']))
        {
            // 循环白名单
            foreach ($domain as $v)
            {
                // 判断是否相同
                if ($v == $url['host'])
                {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 检测域名
     * @param $url
     * @param $domain
     * @return int|string
     */
    protected static function parseDomain(&$url, $domain)
    {
        if ($domain)
        {
            if (true === $domain)
            {
                // 自动判断域名
                $domain = $_SERVER['HTTP_HOST'];
            }
            else
            {
                $domain .= strpos($domain, '.') ? '' : strstr($_SERVER['HTTP_HOST'], '.');
            }
            $domain = (self::isSsl() ? 'https://' : 'http://') . $domain;
        }
        else
        {
            $domain = '';
        }
        return $domain;
    }

    /**
     * 判断是否SSL协议
     * @return boolean
     */
    public static function isSsl()
    {
        if (isset($_SERVER['HTTPS'])
                && ('1' == $_SERVER['HTTPS']
                || 'on' == strtolower($_SERVER['HTTPS']))
        )
        {
            return true;
        }
        elseif (isset($_SERVER['SERVER_PORT'])
            && ('443' == $_SERVER['SERVER_PORT']))
        {
            return true;
        }
        return false;
    }

    /**
     * 匹配路由地址
     * @param $alias
     * @param array $vars
     * @return bool|mixed
     */
    public static function getRouteUrl($alias, &$vars = [])
    {
        foreach ($alias as $key => $val)
        {
            list($url, $pattern, $param) = $val;
            // 解析安全替换
            if (strpos($url, '$'))
            {
                $url = str_replace('$', '[--think--]', $url);
            }
            // 检查变量匹配
            $array = $vars;
            if ($pattern && self::pattern($pattern, $vars))
            {
                foreach ($pattern as $key => $val)
                {
                    if (isset($vars[$key]))
                    {
                        $url = str_replace(['[:' . $key . ']', '<' . $key . '?>', ':' . $key . '', '<' . $key . '>'], $vars[$key], $url);
                        unset($array[$key]);
                    }
                    else
                    {
                        $url = str_replace(['[:' . $key . ']', '<' . $key . '?>'], '', $url);
                    }
                }
                $match = true;
            }

            if (empty($pattern) && empty($param))
            {
                // 没有任何变量
                return $url;
            }
            elseif (!empty($match) || (!empty($param)
                    && array_intersect_assoc($param, $array) == $param))
            {
                // 存在变量定义
                $vars = array_diff_key($array, $param);
                return $url;
            }
        }
        return false;
    }
    /***
    * 获取腾讯实际的 去除广告
    * 参考： https://www.jiezhe.net/post/38.html
    * 
    * @param  $url 需要转换地址
    * @return $url 转换后的地址
    */
    public static function getQqVideoUrl($url){
    	
    	//$url  https://v.qq.com/x/page/b0136et5ztz.html
    	if(empty($url)) return "";
    	if(!strpos($url,"v.qq.com")){return $url;}
    	
    	$reg="/(\w+).html$/i";
    	preg_match($reg,$url,$matches);
    	if($matches){
    		$key=$matches[1];
    	}else{
    		//格式不对,原样返回
    		return $url;
    	}
    	$info="http://vv.video.qq.com/getinfo?vids={$key}&platform=101001&charge=0&otype=json";
    	$arr=file_get_contents($info);
    	$pos=strpos($arr,"={",0);
    	$t='['.trim(str_replace(substr($arr,0,$pos+1), '', $arr),";")."]";
    	$info=json_decode($t,true);
    	//print_r($info[0]['vl']['vi'][0]);
    	$fn		=@$info[0]['vl']['vi'][0]['fn'];
    	$fvkey	=@$info[0]['vl']['vi'][0]['fvkey'];
    	$ul		=@$info[0]['vl']['vi'][0]['ul'];
    	if(is_array($ul)){
    		$qqurl	=@$info[0]['vl']['vi'][0]['ul']['ui'][1]['url'];
    	}else{
    		$qqurl	="";
    	}
    	if(empty($qqurl)){
    		return $url;
    	}else{
    		return $qqurl.$fn.'?vkey='.$fvkey;
    	}
    }
}