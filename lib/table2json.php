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
    return json_encode($values);
}