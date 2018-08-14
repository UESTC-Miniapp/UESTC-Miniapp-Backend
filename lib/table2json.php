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
    $key_arr = array(
        'semester', 'course_code',
        'course_id', 'course_name',
        'course_type', 'point',
        'grade', 'makeup_grade',
        'final_grade', 'gpa'
    );
    $grade_trans = array(
        '优秀' => 95, '良好' => 85, '中等' => 75, '及格' => 65, '不及格' => 55,
        '通过' => 85, '不通过' => 0,
        'A' => 90, 'B' => 85, 'C' => 75, 'D' => 65, 'E' => 55,
        'A+' => 92, 'B+' => 87, 'C+' => 77, 'D+' => 67, 'E+' => 57,
        'A-' => 88, 'B-' => 83, 'C-' => 73, 'D-' => 63, 'E-' => 53
    );

    $values = array();

    foreach ($table_arr[0] as &$value) {
        $value = str_replace('style=""', '', $value);
        preg_match_all('/<td>.*?<\/td>/', $value, $value);
        $pairs = array();
        foreach ($value[0] as $key => &$vvalue) {
            $vvalue = str_replace('<td>', '', $vvalue);
            $vvalue = str_replace('</td>', '', $vvalue);
            if ($key > 4) {//成绩格式化处理
                if (is_numeric($vvalue))//数字字符串，直接转
                    $vvalue = (float)$vvalue;
                else {
                    $vvalue = $grade_trans[$vvalue];
                }
            }
            $pairs[$key_arr[$key]] = $vvalue;
        }
        $values[] = $pairs;
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

    $key_arr = array(
        'course_id', 'course_name', 'date',
        'address', 'number', 'detail'
    );

    foreach ($results as $key => $value) {
        $final_arr[] = array();
        preg_match_all('/<td.*?>\s*(.*?)\s*<\/td>/', $value, $td_arr);//提取所有td标签
        if (strpos($value, '[考试情况尚未发布]')) {
            //处理空值
            $final_arr[$key]['status'] = false;//标记为未发布
            preg_match('/<td colspan\=\"(\d)\">(.*?)<\/td>/', $value, $numbers);
            //numbers[1]为空的数量
            foreach ($td_arr[1] as $kkey => $vvalue) {
                if (strpos($vvalue, '[考试情况尚未发布]')) {
                    for ($i = 0; $i < $numbers[1]; $i += 1) {//补全
                        $final_arr[$key][$key_arr[$kkey + $i]] = '';
                    }
                    continue;
                }
                $final_arr[$key][$key_arr[$kkey]] = $vvalue;
            }
        } else {
            $final_arr[$key]['status'] = true;//标记为正常
            foreach ($td_arr[1] as $kkey => $vvalue) {
                $final_arr[$key][$key_arr[$kkey]] = $vvalue;
            }
        }
    }
    return $final_arr;
}