<?php
/**
 * 交易流水
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

stdlog($_SERVER['REMOTE_ADDR'], 'history');
try {
    if ($_SERVER['REQUEST_METHOD'] != 'POST')
        throw new UMBException(206);

    if (!(array_key_exists('token', $_POST) &&
        array_key_exists('page', $_POST) &&
        array_key_exists('date_range', $_POST) &&
        array_key_exists('type', $_POST)
    ))
        throw new UMBException(206);

    if (!(
        $_POST['page'] &&
        $_POST['date_range'] &&
        $_POST['type']
    ))
        throw new UMBException(206);

    $jwt = jwt_decode($_POST['token']);
    $cookie_str = $jwt['cookie']['ecard'];
    if (!check_ecard($cookie_str))
        throw new UMBException(201);
//以上基本是抄的

    $post_data = array(
        '_transDtl_WAR_ecardportlet_qdate' => $_POST['date_range'],
        '_transDtl_WAR_ecardportlet_qtype' => $_POST['type']
    );
//读取总页数
    $res = post('http://ecard.uestc.edu.cn/web/guest/personal?p_p_id=transDtl_WAR_ecardportlet&p_p_lifecycle=0&p_p_state=exclusive&p_p_mode=view&p_p_col_id=column-4&p_p_col_count=1&_transDtl_WAR_ecardportlet_action=dtlmoreview',
        $post_data,
        $cookie_str);
    if ($res['status'] != 200)
        throw new UMBException(202);

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
    if ($res['status'] != 200)
        throw new UMBException(202);
    $jsons = t2jH($res['body']);

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
} catch (UMBException $e) {
    echo json_encode([
        'success' => false,
        'error_code' => $e->getCode(),
        'error_msg' => $e->getMessage()
    ]);
}