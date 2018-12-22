<?php
/**
 * 查成绩用的
 * html table转json
 */
/**
 * @param string $table_str
 * @return array
 * @author hzy
 */
function t2j(string $table_str) //for grade.php
{
    $table_str = preg_replace('/(\n|\r|\t)/', '', $table_str);
    preg_match_all('/<tbody.*?>(.*?)<\/tbody>/', $table_str, $tbodys);

    //preg_match('/<tbody.*<\/tbody>/', $table_str, $tbodys);
    preg_match_all('/<tr.*?>(.*?)<\/tr>/', $tbodys[1][0], $table_arr);

    //summary
    foreach ($table_arr[1] as $key => $value){
        if(strpos($value,'在校汇总'))
            preg_match_all('/<th.*?>(.*?)<\/th>/',
                $table_arr[1][$key], $sum_arr);
    }
    $summary = array(
        'aver_gpa' => (float)str_replace(' ', '', $sum_arr[1][3]),
        'sum_point' => (float)str_replace(' ', '', $sum_arr[1][2]),
        'course_count' => (int)str_replace(' ', '', $sum_arr[1][1]),
        'time' => time()
    );

    //semester_summary
    $semester_summary = array();
    preg_match_all('/<tr.*?>(.*?)<\/tr>/', $tbodys[1][0], $ssum_arr);
    foreach ($ssum_arr[1] as $value) {
        if (strpos($value, '在校汇总'))
            break;

        preg_match_all('/<td>(.*?)<\/td>/', $value, $single_arr);
        $semester_summary[] = array(
            'semester_year' => $single_arr[1][0],
            'semester_term' => (int)str_replace(' ', '', $single_arr[1][1]),
            'course_count' => (int)str_replace(' ', '', $single_arr[1][2]),
            'sum_point' => (float)str_replace(' ', '', $single_arr[1][3]),
            'aver_gpa' => (float)str_replace(' ', '', $single_arr[1][4])
        );
    }

    //detail
    $grade_trans = array(
        '优秀' => 95, '良好' => 85, '中等' => 75, '及格' => 65, '不及格' => 55,
        '通过' => 85, '不通过' => 0,
        'A' => 90, 'B' => 85, 'C' => 75, 'D' => 65, 'E' => 55,
        'A+' => 92, 'B+' => 87, 'C+' => 77, 'D+' => 67, 'E+' => 57,
        'A-' => 88, 'B-' => 83, 'C-' => 73, 'D-' => 63, 'E-' => 53
    );
    $key_arr = array(
        'semester', 'course_code',
        'course_id', 'course_name',
        'course_type', 'point',
        'grade', 'final_grade'
    );
    $detail = array();
    preg_match_all('/<tr.*?>(.*?)<\/tr>/', $tbodys[1][1], $course_arr);

    //处理可能有的补考
    preg_match_all('/<td.*?>(.*?)<\/td>/', $course_arr[1][0], $sbj_arr);
    if (count($sbj_arr[1]) == 9) {
        $key_arr = array(
            'semester', 'course_code',
            'course_id', 'course_name',
            'course_type', 'point',
            'grade', 're_grade', 'final_grade'
        );
    }
    foreach ($course_arr[1] as $value) {
        preg_match_all('/<td.*?>(.*?)<\/td>/', $value, $sbj_arr);
        $course = array();
        foreach ($sbj_arr[1] as $key => $vvalue) {
            if ($key < 5) {//String直接塞
                if (strpos($vvalue, '<')) {//去除标签
                    $vvalue = preg_replace('/<.*?>/', '', $vvalue);
                }
                $course[$key_arr[$key]] = $vvalue;
            } else {
                $vvalue = str_replace(' ', '', $vvalue);//去除空格
                if ($vvalue == '') {//处理空值
                    $course[$key_arr[$key]] = null;
                    continue;
                }
                if (is_numeric($vvalue))//是的话就直接塞
                    $course[$key_arr[$key]] = (float)$vvalue;
                else//通过上面的转义
                    $course[$key_arr[$key]] = $grade_trans[$vvalue];
            }
        }
        $detail[] = $course;
    }

    return array(
        'summary' => $summary,
        'semester_summary' => $semester_summary,
        'detail' => $detail
    );
}

function t2jE($table_str)//for exam.php
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
        'course_id', 'course_name', 'date', 'plan',
        'address', 'number', 'detail', 'other'
    );

    foreach ($results as $key => $value) {
        $final_arr[] = array();
        preg_match_all('/<td.*?>\s*(.*?)\s*<\/td>/', $value, $td_arr);//提取所有td标签
        if (strpos($value, '[考试情况尚未发布]')) {
            //处理空值
            $final_arr[$key]['status'] = false;//标记为未发布
            preg_match('/<td colspan\=\"(\d)\">(.*?)<\/td>/', $value, $numbers);
            //numbers[1]为空的数量
            $i = 0;
            foreach ($td_arr[1] as $kkey => $vvalue) {
                if (strpos($vvalue, '[考试情况尚未发布]')) {
                    for (; $i < $numbers[1]; $i += 1) {//补全
                        $final_arr[$key][$key_arr[$kkey + $i]] = '';
                    }
                    $i -= 1;
                    continue;
                }
                $final_arr[$key][$key_arr[$kkey + $i]] = $vvalue;
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

function t2jP($table_str)
{
    preg_match(
        '/<table.*?studentInfoTb.*?>([\s\S]*?)<\/table>/',
        $table_str,
        $table_arr);
    preg_match('/姓名：<\/td>[\s\S]*?<td>(.*?)<\/td>/',
        $table_arr[1],
        $name_arr);
    //$name = $name_arr[1];
    preg_match('/英文名：<\/td>[\s\S]*?<td>(.*?)<\/td>/',
        $table_arr[1],
        $ename_arr);
    preg_match('/专业：<\/td>[\s\S]*?<td>(.*?)<\/td>/',
        $table_arr[1],
        $major_arr);
    preg_match('/院系：<\/td>[\s\S]*?<td>(.*?)<\/td>/',
        $table_arr[1],
        $college_arr);
    preg_match('/学籍状态：<\/td>[\s\S]*?<td>(.*?)<\/td>/',
        $table_arr[1],
        $status_arr);
    preg_match('/学生类别：<\/td>[\s\S]*?<td>(.*?)<\/td>/',
        $table_arr[1],
        $type_arr);
    preg_match('/所属校区：<\/td>[\s\S]*?<td>(.*?)<\/td>/',
        $table_arr[1],
        $campus_arr);
    return array(
        'name' => $name_arr[1],
        'ename' => $ename_arr[1],
        'major' => $major_arr[1],
        'college' => $college_arr[1],
        'status' => $status_arr[1],
        'type' => $type_arr[1],
        'campus' => $campus_arr[1]
    );
}

function t2jH($table_str)
{
    preg_match_all("/<tr>([\s\S]*?)<\/tr>/", $table_str, $table_arr);
    //第一行数据为标题，需要略去
    unset($table_arr[1][0]);
    $ret = array(
        'json' => array(),
        'payment' => null,
        'charge' => null
    );
    $key_name = array(
        'date',
        'time',
        'device',
        'price',
        'balance'
    );
    $tmp_arr = array(
        'date' => '',
        'time' => '',
        'device' => '',
        'price' => '',
        'balance' => ''
    );
    foreach ($table_arr[1] as $key => $value) {
        //处理内部值
        //$ret['json'][] = array();
        preg_match_all("/<td.*?>(.*?)<\/td>/", $value, $th_arr);
        foreach ($th_arr[1] as $kkey => $vvalue) {
            if (strpos($vvalue, 'span')) {//去除span标签
                $vvalue = preg_replace("/<span .*?>/", '', $vvalue);
                $vvalue = str_replace('</span>', '', $vvalue);
            }
            if ($kkey < 2)
                $tmp_arr[$key_name[$kkey]] = (int)$vvalue;
            else if ($kkey > 2)
                $tmp_arr[$key_name[$kkey]] = (float)$vvalue;
            else
                $tmp_arr[$key_name[$kkey]] = $vvalue;
        }
        $ret['json'][] = $tmp_arr;
    }
    if (strpos($table_str, '共计')) {
        preg_match("/消费.*?>(.*?)<\/span>/", $table_str, $payment_arr);
        $ret['payment'] = (float)$payment_arr[1];
        preg_match("/充值.*?>(.*?)<\/span>/", $table_str, $charge_arr);
        $ret['charge'] = (float)$charge_arr[1];
    } else {
        $ret['payment'] = null;
        $ret['charge'] = null;
    }
    return $ret;
}

function t2jER($table_str)
{
    preg_match('/<tbody\s.*?>([\s\S]*?)<\/tbody>/', $table_str, $tbody_arr);
    preg_match_all('/<tr>([\s\S]*?)<\/tr>/', $tbody_arr[1], $tr_arr);
    $key_name = array(
        'semester',
        'application_date',
        'status',
        'verifier',
        'verify_date'
    );
    $ret = array();
    foreach ($tr_arr[1] as $key => $value) {
        $ret[] = array();
        preg_match_all('/<td>(.*?)<\/td>/', $value, $td_arr);
        foreach ($td_arr[1] as $kkey => $vvalue) {
            $ret[$key][$key_name[$kkey]] = $vvalue;
        }
    }
    return $ret;
}