<?php
/**
 * 读取考试信息
 */
header('Content-type: application/json');

require 'lib/url.php';
require 'lib/dbconf.php';
require 'lib/checkstr.php';
require 'lib/table2json.php';
require 'lib/check_eams.php';
require 'lib/jwt_parse.php';
require 'lib/exception.php';

stdlog($_SERVER['REMOTE_ADDR'], 'exam');

try {
    if ($_SERVER['REQUEST_METHOD'] != 'POST')
        throw new UMBException(206);

    if (!(
        array_key_exists('token', $_POST) &&
        array_key_exists('semesterId', $_POST) &&
        array_key_exists('examTypeId', $_POST)
    ))
        throw new UMBException(206);

    $jwt = jwt_decode($_POST['token']);
    $cookie_str = $jwt['cookie']['eams'] . ';' . $jwt['cookie']['idas'];
    if (!check_eams($cookie_str))
        throw new UMBException(201);
//以上基本都是抄grade.php的，我觉得甚至可以写个函数

    if ($_POST['semesterId'] != '') {
        define('EXAM_URL',
            'http://eams.uestc.edu.cn/eams/stdExamTable!examTable.action?semester.id=' .
            $_POST['semesterId'] .
            '&examType.id=' .
            $_POST['examTypeId'] .
            '&_=' .
            (string)time() . '000');
    } else {
        define('EXAM_URL',
            'http://eams.uestc.edu.cn/eams/stdExamTable!examTable.action?semester.id=' .
            get('http://eams.uestc.edu.cn/eams/stdExamTable.action', $cookie_str)['cookie']['semester.id'] .
            '&examType.id=' .
            $_POST['examTypeId'] .
            '&_=' .
            (string)time() . '000');
    }
    $res = get(EXAM_URL, $cookie_str);
    if ($res['status'] != 200)
        throw new UMBException(202);

    echo json_encode([
        'success' => true,
        'error_code' => null,
        'error_msg' => '',
        'data' => t2jE($res['body'])
    ]);
} catch (UMBException $e) {
    echo json_encode([
        'success' => false,
        'error_code' => $e->getCode(),
        'error_msg' => $e->getMessage()
    ]);
}