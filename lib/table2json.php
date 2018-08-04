<?php
/**
 * 查成绩用的
 * html table转json
 */

function t2j($table_str)
{
    $table_str = str_replace("\n", '', $table_str);
    $table_str = str_replace("\r", '', $table_str);
    $table_str = str_replace(' ', '', $table_str);
    $table_str = str_replace("\t", '', $table_str);
    preg_match('/<tbody.*<\/tbody>/', $table_str, $tbodys);
    preg_match_all('/<tr>.+?<\/tr>/', $tbodys[0], $table_arr);
    $values = array();
    foreach ($table_arr[0] as &$value) {
        $value = str_replace('style=""', '', $value);
        preg_match_all('/<td>.*?<\/td>/', $value, $value);
        foreach ($value[0] as &$vvalue) {
            $vvalue = str_replace('<td>', '', $vvalue);
            $vvalue = str_replace('</td>', '', $vvalue);
        }
        $values[] = $value[0];
    }
    return $values;
}

function t2jE($table_str)
{
    $nstr = str_replace("\n", '', $table_str);
    $nstr = str_replace("\r", '', $nstr);
    preg_match('/<table.*?>(.*?)<\/table>/', $nstr, $tables);
    preg_match_all('/<tr.*?>\s*(.*?)\s*<\/tr>/', $tables[1], $tds);
    $results = $tds[1];
    array_splice($results, 0, 1);//去除标题
    //处理课程字符串
    $final_arr = array();
    foreach ($results as $key => $value) {
        $final_arr[] = array();
        preg_match_all('/<td.*?>\s*(.*?)\s*<\/td>/', $value, $td_arr);//提取所有td标签
        if (strpos($value, '[考试情况尚未发布]')) {
            //处理空值
            $final_arr[$key][] = 0;//标记为未发布
            preg_match('/<td colspan\=\"(\d)\">(.*?)<\/td>/', $value, $numbers);
            //numbers[1]为空的数量
            foreach ($td_arr[1] as $vvalue) {
                if (strpos($vvalue, '[考试情况尚未发布]')) {
                    for ($i = 1; $i <= $numbers[1]; $i += 1) {//补全
                        $final_arr[$key][] = '';
                    }
                    continue;
                }
                $final_arr[$key][] = $vvalue;
            }
        } else {
            $final_arr[$key][] = 1;//标记为正常
            foreach ($td_arr[1] as $vvalue) {
                $final_arr[$key][] = $vvalue;
            }
        }
    }
    return $final_arr;
}