<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/12/21
 * Time: 9:45
 */
Class Readexcelfile
{
    /**
     * 读取excel文件
     * @param $url 目标地址
     * @param $data 请求参数
     * @return $resutl 请求返回结果
     */
    public static function readerExcel($file,$type ='Excel5')
    {
        //设置时区
        date_default_timezone_set('Asia/ShangHai');
        //判断临时文件是否存在
        if (!file_exists($file)) {
            exit("not found ".$file."\r\n");
        }
        //判断是csv还是excel5,还是excel2007
        if($type=='csv')
        {
            $reader = new PHPExcel_Reader_CSV();
            $reader->setDelimiter(',');
            $reader->setInputEncoding('GBK');
        }
        else
        {
            $reader = PHPExcel_IOFactory::createReader($type);
        }

        $PHPExcel = $reader->load($file); // 载入excel文件
        $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数

        /** 循环读取每个单元格的数据 */
        for ($row = 1; $row <= $highestRow; $row++) {//行数是以第1行开始
            for ($column = 'A'; $column <= $highestColumm; $column++) {//列数是以A列开始
                $dataset[$row][] = $sheet->getCell($column . $row)->getValue();
            }
        }
        return $dataset;
    }/**
 * 读取excel文件
 * @param $url 目标地址
 * @param $data 请求参数
 * @return $resutl 请求返回结果
 */
    public static function readercsv($file)
    {
        $allfile = fopen($file,'r');
        while ($data = fgetcsv($allfile))
        {
            foreach ($data as $k=>&$v)
            {
                $v = mb_convert_encoding($v,'utf-8','gb2312');
            }
            $goods_list[] = $data;
        }

        /* foreach ($goods_list as $arr){
            if ($arr[0]!=""){
                echo $arr[0]."<br>";
            }
        } */

        fclose($file);

        return $goods_list;
    }
}

