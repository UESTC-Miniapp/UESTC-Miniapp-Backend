<?php
/**
 * 读取成绩信息
 */
header('Content-type: application/json');

require_once __DIR__ . '/lib/url.php';
require_once __DIR__ . '/lib/dbconf.php';
require_once __DIR__ . '/lib/jwt_parse.php';
require_once __DIR__ . '/lib/exception.php';
require_once __DIR__ . '/lib/checkstr.php';
require_once __DIR__ . '/lib/table2json.php';
require_once __DIR__ . '/lib/check_eams.php';

stdlog($_SERVER['REMOTE_ADDR'], 'grade');

try {
    if ($_SERVER['REQUEST_METHOD'] != 'POST')
        throw new UMBException(206);

    if (!array_key_exists('token', $_POST))
        throw new UMBException(206);

    if (!jwt_check($_POST['token']))
        throw new UMBException(201);

    $jwt = jwt_decode($_POST['token']);
    $cookie_str = $jwt['cookie']['eams'] . ';' . $jwt['cookie']['idas'];
    if (!check_eams($cookie_str))
        throw new UMBException(201);

    $res = get(
        'http://eams.uestc.edu.cn/eams/teach/grade/course/person!historyCourseGrade.action?projectType=MAJOR',
        $cookie_str);
    if ($res['status'] != 200)
        throw new UMBException(202);

    echo json_encode([
        'success' => true,
        'error_code' => null,
        'error_msg' => '',
        'data' => t2j($res['body'])
    ]);
} catch (UMBException $e) {
    echo json_encode([
        'success' => false,
        'error_code' => $e->getCode(),
        'error_msg' => $e->getMessage()
    ]);
}