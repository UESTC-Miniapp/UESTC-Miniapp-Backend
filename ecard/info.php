<?php
/**
 * 一卡通信息
 */

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

file_put_contents('../log.php', date('c') . ',' . $_SERVER['REMOTE_ADDR'] . ',' . "ecard_info,\n", FILE_APPEND);
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

//开始请求数据
$res = get('http://ecard.uestc.edu.cn/web/guest/personal', $cookie_str);
preg_match('/卡号：(\d{2,8})/', $res['body'], $num_arr);
preg_match('/卡余额：.*?>(.*?)<\/span>元/', $res['body'], $balance_arr);
preg_match('/卡状态：(.*?)<\/td>/', $res['body'], $status_arr);
preg_match('/卡有效期：(.*?)<\/td>/', $res['body'], $date_arr);
preg_match('/充值未领取：.*?>(.*?)<\/span>元/', $res['body'], $un_bal_arr);
echo json_encode(array(
    'success' => true,
    'error_code' => null,
    'error_msg' => '',
    'data' => array(
        'number' => $num_arr[1],
        'balance' => $balance_arr[1],
        'status' => $status_arr[1],
        'date' => $date_arr[1],
        'uncashed_balance' => $un_bal_arr[1]
    )
));