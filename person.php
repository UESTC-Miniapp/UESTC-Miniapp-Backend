<?php
/**
 * 读取个人信息
 */
header('Content-type: application/json');
require 'lib/url.php';
require 'lib/dbconf.php';
require 'lib/checkstr.php';
require 'lib/table2json.php';
require 'lib/check_eams.php';
require 'lib/jwt_parse.php';
require 'lib/exception.php';

stdlog($_SERVER['REMOTE_ADDR'], 'person');
try {
    if ($_SERVER['REQUEST_METHOD'] != 'POST')
        throw new UMBException(206);

    if (!array_key_exists('token', $_POST))
        throw new UMBException(206);
    $jwt = jwt_decode($_POST['token']);
    $cookie_str = $jwt['cookie']['eams'] . ';' . $jwt['cookie']['idas'];
    if (!check_eams($cookie_str))
        throw new UMBException(201);

//发送请求
    $res = get(
        'http://eams.uestc.edu.cn/eams/stdDetail.action?_=' . (string)time() . '000',
        $cookie_str);
    if (strpos($res['body'], '!innerIndex'))//临时补丁
        $res = get(
            'http://eams.uestc.edu.cn/eams/stdDetail!innerIndex.action?_=' . (string)time() . '000',
            $cookie_str);

    if ($res['status'] != 200)
        throw new UMBException(202);

    echo json_encode(array(
        'success' => true,
        'error_code' => null,
        'error_msg' => '',
        'data' => t2jP($res['body'])
    ));
}
catch (UMBException $e){
    echo json_encode([
        'success' => false,
        'error_code' => $e->getCode(),
        'error_msg' => $e->getMessage()
    ]);
}