<?php
/**
 * 一卡通信息
 */
header('Content-type: application/json');
require '../lib/dbconf.php';
require '../lib/jwt_parse.php';
require '../lib/exception.php';
require '../lib/url.php';
require '../lib/checkstr.php';
require '../lib/err_msg.php';
require '../lib/check_ecard.php';
require '../lib/table2json.php';
stdlog($_SERVER['REMOTE_ADDR'], 'info');

try {
    if ($_SERVER['REQUEST_METHOD'] != 'POST')
        throw new UMBException(206);

    if (!array_key_exists('token', $_POST))
        throw new UMBException(206);

    $jwt = jwt_decode($_POST['token']);
    $cookie_str = $jwt['cookie']['ecard'];
    if (!check_ecard($cookie_str))
        throw new UMBException(201);
//以上基本是抄的

//开始请求数据
    $res = get('http://ecard.uestc.edu.cn/web/guest/personal', $cookie_str);
    if ($res['status'] != 200)
        throw new UMBException(202);
    preg_match('/你好，(.*?)，欢迎回来！/', $res['body'], $nickname_arr);
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
            'nickname' => $nickname_arr[1],
            'number' => $num_arr[1],
            'balance' => $balance_arr[1],
            'status' => $status_arr[1],
            'date' => $date_arr[1],
            'uncashed_balance' => $un_bal_arr[1]
        )
    ));
} catch (UMBException $e) {
    echo json_encode([
        'success' => false,
        'error_code' => $e->getCode(),
        'error_msg' => $e->getMessage()
    ]);
}