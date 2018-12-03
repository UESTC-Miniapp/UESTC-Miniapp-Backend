<?php
/**
 * 消费趋势
 */
header('Content-type: application/json');
require '../lib/url.php';
require '../lib/checkstr.php';
require '../lib/dbconf.php';
require '../lib/err_msg.php';
require '../lib/check_ecard.php';
require '../lib/table2json.php';
//require '../for_debug.php';//仅用于调试

function err($code)
{
    return json_encode(array(
        'success' => false,
        'error_code' => $code,
        'error_msg' => err_msg($code, E_GEN),
        'data' => array()
    ));
}

file_put_contents('../log.php', date('c') . ',' . $_SERVER['REMOTE_ADDR'] . ',' . "ecard_stat,\n", FILE_APPEND);
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo err(203);
    exit;
}
if (!(array_key_exists('username', $_POST) &&
    array_key_exists('token', $_POST))) {
    echo err(203);
    exit;
}
if (!check_username($_POST['username'])) {
    echo err(201);
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
    "SELECT `token`,`ecard_cookie` FROM " .
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
$cookie_str = $query_res[1];
if (!check_ecard($cookie_str)) {
    echo err(201);
    exit;
}
//以上基本是抄的

//请求数据后直接发回就行
$res = post('http://ecard.uestc.edu.cn/web/guest/myactive?p_p_id=myActive_WAR_ecardportlet&p_p_lifecycle=2&p_p_state=normal&p_p_mode=view&p_p_cacheability=cacheLevelPage&p_p_col_id=column-1&p_p_col_count=1&p_p_resource_id=' .
    'consumeStat',
    array('_myActive_WAR_ecardportlet_days' => '30'),
    $cookie_str);
if ($res['status'] != 200) {
    echo err(203);
    exit;
}
echo json_encode(array(
    'success' => true,
    'error_code' => null,
    'error_msg' => '',
    'data' => json_decode($res['body'])
));