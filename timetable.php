<?php
/**
 * 课程表
 * 此部分调用了yidadaa编写的课程表解析
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
require 'for_debug/tt-debug.php';//仅用于调试
function err($code)
{
    return json_encode(array(
        'success' => false,
        'error_code' => $code,
        'error_msg' => err_msg($code, E_TT),
        'data' => array()
    ));
}

file_put_contents('log.php', date('c') . ',' . $_SERVER['REMOTE_ADDR'] . ',' . "timet,\n", FILE_APPEND);
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo err(203);
    exit;
}

if (!(array_key_exists('username', $_POST) &&
        array_key_exists('token', $_POST)) &&
    array_key_exists('semesterId', $_POST)) {
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
if ($query_res[0] != hash('sha256', $_POST['token'])) {
    echo err(201);
    exit;
}
$cookie_str = $query_res[2] . ';' . $query_res[1];
if (!check_eams($cookie_str)) {
    echo err(201);
    exit;
}

//请求课程表
define('TT_URL', 'http://eams.uestc.edu.cn/eams/courseTableForStd!courseTable.action');
$data = array(
    'ignoreHead' => '1',
    'setting.kind' => 'std',
    'startWeek' => '1',
    'project.id' => '1',
    'ids' => '142846'
);
if ($_POST['semesterId'] == '') {//手动获取semesterId
    $res = get(
        'http://eams.uestc.edu.cn/eams/courseTableForStd.action?_=' . (string)time() . '000',
        $cookie_str);
    if ($res['status'] != 200) { //不是200的话不正常
        echo err(202);
        exit;
    }
    $data['semester.id'] = $res['cookie']['semester.id'];

} else {
    $data['semester.id'] = $_POST['semesterId'];
}
$res = post(TT_URL, $data, $cookie_str);
if ($res['status'] != 200) {
    echo err(202);
    exit;
}

//把html直接灌给zyf写的js
$res2 = post('http://localhost:30001/', array('content' => $res['body']));
echo json_encode(array(
    'success' => true,
    'error_code' => null,
    'error_msg' => '',
    'data' => json_decode($res2['body'])
));