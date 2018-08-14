<?php
/**
 * 读取成绩信息
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
if (!require_once 'lib/err_msg.php')
    require 'lib/err_msg.php';
//require 'for_debug/grade-debug.php';//仅用于调试
function err($code)
{
    return json_encode(array(
        'success' => false,
        'error_code' => $code,
        'error_msg' => err_msg($code, E_GRADE),
        'data' => array()
    ));
}

if ($_SERVER['REQUEST_METHOD'] != 'POST'){
    echo err(203);
    exit;
}

if (!(array_key_exists('username', $_POST) &&
    array_key_exists('token', $_POST) &&
    array_key_exists('semesterId', $_POST))) {
    echo err(203);
    exit;
}
if (!check_username($_POST['username'])) {
    err(201);
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
    echo err(202);
    exit;
}

$query_res = $db->query(
    "SELECT `token`,`uestc_cookie`,`eams_cookie` FROM " .
    "`user_info` WHERE `student_number` = '{$_POST['username']}' LIMIT 1")
    ->fetch_all()[0];
if (!$query_res) {//什么都没有
    echo err(201);
    exit;
}
if ($query_res[0] != hash('sha256',$_POST['token'])) {
    echo err(201);
    exit;
}
$cookie_str = $query_res[2] . ';' . $query_res[1];
if (!check_eams($cookie_str)) {
    echo err(201);
    exit;
}

if ($_POST['semesterId'] != '') {
    define('GRADE_URL',
        'http://eams.uestc.edu.cn/eams/teach/grade/course/person!search.action?semesterId=' .
        $_POST['semesterId'] . '&projectType=&_=' . (string)time() . '000');
} else {//读取默认学期
    define('GRADE_URL',
        'http://eams.uestc.edu.cn/eams/teach/grade/course/person!search.action?semesterId=' .
        get('http://eams.uestc.edu.cn/eams/teach/grade/course/person.action', $cookie_str)['cookie']['semester.id']
        . '&projectType=&_=' . (string)time() . '000');
}
$res = get(GRADE_URL, $cookie_str);
if ($res['status'] != 200) {
    echo err(202);
    exit;
}

echo json_encode(array(
    'success'=>true,
    'error_code'=>null,
    'error_msg'=>'',
    'data' => t2j($res['body'])
));