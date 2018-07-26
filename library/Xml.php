<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/4/1
 * Time: 下午6:07
 */
class Xml
{
    /**
     * 将xml转换为Array数组
     * @param string $xml
     * @param mixed $case 是否需要键小写
     * @return array
     */
    public static function toArray($xml='', $case=CASE_LOWER)
    {
        // 若为空，则直接返回
        if (empty($xml))
        {
            return [];
        }
        $xml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $xml = json_decode(json_encode($xml), true);
        // 判断是否需要控制键大小写
        if ($case !== false && in_array($case, [0,1]))
        {
            return array_change_key_case($xml, $case);
        }
        return $xml;
    }

    /**
     * 将Array数组换换为XML字符串
     * @param array $data
     * @param bool $need_xml
     * @return array
     */
    public static function make(Array $data=[], $need_xml=true)
    {
        $s = $need_xml ? "<xml>" : '';
        foreach ($data as $tagname => $value)
        {
            if (is_numeric($tagname))
            {
                $tagname = $value['TagName'];
                unset($value['TagName']);
            }
            if (is_array($value))
            {
                $s .= "<{$tagname}>" . self::make($value, false) . "</{$tagname}>";
            }
            else
            {
                $s .= "<{$tagname}>" . (!is_numeric($value) ? '<![CDATA[' : '') . $value . (!is_numeric($value) ? ']]>' : '') . "</{$tagname}>";
            }
        }
        $s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);
        return $need_xml ? $s . "</xml>" : $s;
    }
}