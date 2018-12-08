<?php
/**
 * 用于测试token对应的cookie是否还有效
 */

header('Content-type: application/json');

//这文件没鸟用了，暂时
/*
echo "{\"success\":true}";
exit;
*/
require 'lib/checkstr.php';
require 'lib/url.php';
require 'lib/dbconf.php';//数据库相关
require 'lib/3rd_lib/simple_html_dom.php';
require 'lib/err_msg.php';
require 'lib/check_eams.php';
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

$client = new Client();

//require 'for_debug/check_token-debug.php';

function err($code)
{
    return json_encode(array(
        'token_is_available' => false,
        'success' => false,
        'error_code' => $code,
        'error_msg' => err_msg($code, E_CHECK)
    ));
}

file_put_contents('log.php', date('c') . ',' . $_SERVER['REMOTE_ADDR'] . ',' . "check,\n", FILE_APPEND);
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

define('URL', 'http://idas.uestc.edu.cn/authserver/index.do');
//检测是否发生302跳转

$db = new mysqli();
$db->connect(
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_NAME,
    DB_PORT
);
if ($db->connect_errno) {//连接失败
    echo err(202);
    exit;
}
$cookie_arr = $db->query(
    "SELECT `idas_cookie`,`uestc_cookie`,`token`,`eams_cookie`,`ecard_cookie` FROM `user_info` WHERE `student_number`='{$_POST["username"]}'"
)->fetch_all()[0];
if ($cookie_arr && $cookie_arr[2] == hash('sha256', $_POST['token'])) {//没找到||不一致
    //$cookie_str = $cookie_arr[0].';'.$cookie_arr[1];
    //$res = get(URL,$cookie_str);

    //改用guzzle
    try {
        $res = $client->request('GET', URL, [
            'headers' => [
                'Cookie' => $cookie_arr[0] . ';' . $cookie_arr[1]
            ],
            'allow_redirects' => [
                'max' => 100,
                'track_redirects' => false
            ]
        ]);
    } catch (RequestException $e) {
        echo err(202);
        exit;
    } catch (GuzzleException $e) {
        echo err(202);
        exit;
    }
    $res_body = (string)$res->getBody();

    //关于神奇现象
    if (!$res_body) {
        echo err(202);
        exit;
    }

    $html = new simple_html_dom();
    $html->load($res_body);
    $title = $html->find('title', 0);
    if ($title->innerText() == '电子科技大学登录') {
        //echo err(201);
        echo json_encode(array(
            'token_is_available' => false,
            'success' => true,
            'error_code' => null,
            'error_msg' => ''
        ));
        exit;
    } else {
        //检测教务处cookie
        if (!check_eams($cookie_arr[1] . ';' . $cookie_arr[3])) {
            echo json_encode(array(
                'token_is_available' => false,
                'success' => true,
                'error_code' => null,
                'error_msg' => ''
            ));
            exit;
        }
        //检测一卡通
        $res = get('http://ecard.uestc.edu.cn/web/guest/personal',
            $cookie_arr[4] . ';' . $cookie_arr[1]);
        if ($res['status'] != 200) {
            echo json_encode(array(
                'token_is_available' => false,
                'success' => true,
                'error_code' => null,
                'error_msg' => ''
            ));
            exit;
        }
        //token有效
        echo json_encode(array(
            'token_is_available' => true,
            'success' => true,
            'error_code' => null,
            'error_msg' => ''
        ));
        exit;
    }
} else {
    echo err(201);
    exit;
}