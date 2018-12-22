<?php
/**
 * 课程表
 * 此部分调用了yidadaa编写的课程表解析
 */
header('Content-type: application/json');
require 'lib/url.php';
require 'lib/dbconf.php';
require 'lib/checkstr.php';
require 'lib/table2json.php';
require 'lib/check_eams.php';
require 'lib/jwt_parse.php';
require 'lib/exception.php';

require 'vendor/autoload.php';

use GuzzleHttp\Client;

stdlog($_SERVER['REMOTE_ADDR'], 'timetable');

//格院课表
function glasgow(&$table)
{

}

try {
    if ($_SERVER['REQUEST_METHOD'] != 'POST')
        throw new UMBException(206);

    if (!(array_key_exists('token', $_POST) &&
        array_key_exists('semesterId', $_POST)))
        throw new UMBException(206);

    $jwt = jwt_decode($_POST['token']);
    $cookie_str = $jwt['cookie']['eams'] . ';' . $jwt['cookie']['idas'];
    if (!check_eams($cookie_str))
        throw new UMBException(201);

//请求课程表
//读取ids
    $res = get('http://eams.uestc.edu.cn/eams/courseTableForStd.action?_=' .
        (string)time() . '000', $cookie_str);

    if (strpos($res['body'], '!innerIndex'))//临时补丁
        $res = get(
            'http://eams.uestc.edu.cn/eams/courseTableForStd!innerIndex.action?_=' .
            (string)time() . '000',
            $cookie_str);

    preg_match_all('/bg\.form\.addInput\(form\,\"ids\"\,\"(.*?)\"\)\;/',
        $res['body'], $result_arr);
    if (strlen($result_arr[1][0]) != 6)//理论上应该是6位的，保守考虑
        throw new UMBException(202);

    $data = array(
        'ignoreHead' => '1',
        'setting.kind' => 'std',
        'startWeek' => '1',
        'project.id' => '1',
        'ids' => $result_arr[1][0]
    );
    if ($_POST['semesterId'] == '') {//手动获取semesterId
        $res = get(
            'http://eams.uestc.edu.cn/eams/courseTableForStd.action?_=' . (string)time() .
            '000', $cookie_str);
        if (strpos($res['body'], '!innerIndex'))//临时补丁
            $res = get(
                'http://eams.uestc.edu.cn/eams/courseTableForStd!innerIndex.action?_=' .
                (string)time() . '000', $cookie_str);

        if ($res['status'] != 200) //不是200的话不正常
            throw new UMBException(202);

        $data['semester.id'] = $res['cookie']['semester.id'];

    } else {
        $data['semester.id'] = $_POST['semesterId'];
    }

    $res = post('http://eams.uestc.edu.cn/eams/courseTableForStd!courseTable.action',
        $data, $cookie_str);

    if ($res['status'] != 200)
        throw new UMBException(202);

//把html直接灌给zyf写的js
    $res2 = post('http://localhost:30001/', array('content' => $res['body']));
    if ($jwt['stu_type'] === 2) //格院，单独处理
        glasgow($res2);

    echo json_encode(array(
        'success' => true,
        'error_code' => null,
        'error_msg' => '',
        'data' => json_decode($res2['body'])
    ));
} catch (UMBException $e) {
    echo json_encode([
        'success' => false,
        'error_code' => $e->getCode(),
        'error_msg' => $e->getMessage()
    ]);
}