<?php
/**
 * 交易流水
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
        'data' => array(
            'pages' => null,
            'date_range' => null,
            'payment' => null,
            'charge' => null,
            'detail' => array()
        )
    ));
}

file_put_contents('../log.php', date('c') . ',' . $_SERVER['REMOTE_ADDR'] . ',' . "ecard_history,\n", FILE_APPEND);
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo err(203);
    exit;
}
if (!(array_key_exists('username', $_POST) &&
    array_key_exists('token', $_POST) &&
    array_key_exists('page', $_POST) &&
    array_key_exists('date_range', $_POST) &&
    array_key_exists('type', $_POST)
)) {
    echo err(203);
    exit;
}
if (!check_username($_POST['username'])) {
    echo err(201);
    exit;
}
if (!(
    $_POST['page'] &&
    $_POST['date_range'] &&
    $_POST['type']
)) {
    echo err(203);
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

$post_data = array(
    '_transDtl_WAR_ecardportlet_qdate' => $_POST['date_range'],
    '_transDtl_WAR_ecardportlet_qtype' => $_POST['type']
);
//读取总页数
$res = post('http://ecard.uestc.edu.cn/web/guest/personal?p_p_id=transDtl_WAR_ecardportlet&p_p_lifecycle=0&p_p_state=exclusive&p_p_mode=view&p_p_col_id=column-4&p_p_col_count=1&_transDtl_WAR_ecardportlet_action=dtlmoreview',
    $post_data,
    $cookie_str);
if ($res['status'] != 200) {
    echo err(203);
    exit;
}
preg_match("_transDtl_WAR_ecardportlet_pageCount.*[\r|\n|\r\n].*?value=\'(.*?)\'",
    $res['body'],
    $count_arr);

if ($_POST['page'] != 1) {
    $post_data['_transDtl_WAR_ecardportlet_cur'] = $_POST['page'];
    $post_data['_transDtl_WAR_ecardportlet_delta'] = $count_arr[1];
}
$res = post('http://ecard.uestc.edu.cn/web/guest/personal?p_p_id=transDtl_WAR_ecardportlet&p_p_lifecycle=0&p_p_state=exclusive&p_p_mode=view&p_p_col_id=column-4&p_p_col_count=1&_transDtl_WAR_ecardportlet_action=dtlmoreview',
    $post_data,
    $cookie_str);
if ($res['status'] != 200) {
    echo err(203);
    exit;
}
$jsons=t2jH($res['body']);

echo json_encode(array(
    'success' => true,
    'error_code' => null,
    'error_msg' => '',
    'data' => array(
        'pages' => (int)$count_arr[1],
        'date_range' => $_POST['date_range'],
        'payment' => $jsons['payment'],
        'charge' => $jsons['charge'],
        'detail' => $jsons['json']
    )
));