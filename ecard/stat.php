<?php
/**
 * 消费趋势
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
stdlog($_SERVER['REMOTE_ADDR'], 'place');

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

//请求数据后直接发回就行
    $res = post('http://ecard.uestc.edu.cn/web/guest/myactive?p_p_id=myActive_WAR_ecardportlet&p_p_lifecycle=2&p_p_state=normal&p_p_mode=view&p_p_cacheability=cacheLevelPage&p_p_col_id=column-1&p_p_col_count=1&p_p_resource_id=' .
        'consumeStat',
        array('_myActive_WAR_ecardportlet_days' => '30'),
        $cookie_str);
    if ($res['status'] != 200)
        throw new UMBException(202);

    echo json_encode(array(
        'success' => true,
        'error_code' => null,
        'error_msg' => '',
        'data' => json_decode($res['body'])
    ));
}
catch(UMBException $e){
    echo json_encode([
        'success' => false,
        'error_code' => $e->getCode(),
        'error_msg' => $e->getMessage()
    ]);
}