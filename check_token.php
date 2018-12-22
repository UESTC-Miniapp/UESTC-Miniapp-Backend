<?php
/**
 * 用于测试token对应的cookie是否还有效，
 * 包含idas;eams;ecard
 */

header('Content-type: application/json');

require 'lib/url.php';
require 'lib/dbconf.php';
require 'lib/checkstr.php';
require 'lib/table2json.php';
require 'lib/check_eams.php';
require 'lib/jwt_parse.php';
require 'lib/exception.php';

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

$client = new Client();

stdlog($_SERVER['REMOTE_ADDR'], 'check_token');
try {
    if ($_SERVER['REQUEST_METHOD'] != 'POST')
        throw new UMBException(206);

    if (!array_key_exists('token', $_POST))
        throw new UMBException(206);

    $jwt = jwt_decode($_POST['token']);

    define('URL', 'http://idas.uestc.edu.cn/authserver/index.do');
//检测是否发生302跳转
    //改用guzzle
    try {
        $res = $client->request('GET', URL, [
            'headers' => [
                'Cookie' => "{$jwt['cookie']['idas']};{$jwt['cookie']['uestc']}"
            ],
            'allow_redirects' => [
                'max' => 100,
                'track_redirects' => false
            ]
        ]);
    } catch (RequestException $e) {
        throw new UMBException(202);
    } catch (GuzzleException $e) {
        throw new UMBException(202);
    }
    $res_body = (string)$res->getBody();

    //关于神奇现象
    if (!$res_body)
        throw new UMBException(202);

    preg_match('/<title>(.*?)<\/title>/', $res_body, $title);
    if ($title[1] !== '统一身份认证') {
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
        if (!check_eams("{$jwt['cookie']['eams']};{$jwt['cookie']['uestc']}")) {
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
            "{$jwt['cookie']['ecard']};{$jwt['cookie']['uestc']}");
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
} catch (UMBException $e) {
    echo json_encode(array(
        'token_is_available' => false,
        'success' => false,
        'error_code' => $e->getCode(),
        'error_msg' => $e->getMessage()
    ));
}