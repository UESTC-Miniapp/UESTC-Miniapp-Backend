<?php
/**
 * 读取考试信息
 */

if (!require_once 'lib/url.php')
    require 'lib/url.php';
if (!require_once 'lib/dbconf.php')
    require 'lib/dbconf.php';
if (!require_once 'lib/checkstr.php')
    require 'lib/checkstr.php';
if (!require_once 'lib/table2json.php')
    require 'lib/table2json.php';
if (!require_once 'lib/check_eams.php')
    require 'lib/check_eams.php';
//require 'for_debug/exam-debug.php';//仅用于调试

if ($_SERVER['REQUEST_METHOD'] != 'POST')
    exit;

function err($code)
{
    return "{\"code\":\"{$code}\"}";
}

if (!(array_key_exists('username', $_POST) &&
    array_key_exists('token', $_POST) &&
    array_key_exists('semesterId', $_POST) &&
    array_key_exists('examTypeId', $_POST))) {
    echo err(4);
    exit;
}
if (!check_username($_POST['username'])) {
    err(4);
    exit;
}

$db = new mysqli();
$db->connect(
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_NAME,
    DB_PORT
);
if ($db->connect_errno) {
    echo err(3);
    exit;
}

$query_res = $db->query(
    "SELECT `token`,`uestc_cookie`,`eams_cookie` FROM " .
    "`user_info` WHERE `student_number` = '{$_POST['username']}' LIMIT 1")
    ->fetch_all()[0];
if (!$query_res) {//什么都没有
    echo err(4);
    exit;
}
if ($query_res[0] != $_POST['token']) {
    echo err(2);
    exit;
}
$cookie_str = $query_res[2] . ';' . $query_res[1];
if (!check_eams($cookie_str)) {
    echo err(2);
    exit;
}
//以上基本都是抄grade.php的，我觉得甚至可以写个库

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
if ($res['status'] != 200) {
    echo err(3);
    exit;
}

echo json_encode(array(
    'code' => '1',
    'content' => t2jE($res['body'])
));