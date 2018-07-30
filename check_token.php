<?php
/**
 * 用于测试token对应的cookie是否还有效
 */

require 'lib/checkstr.php';
require 'lib/url.php';
require 'lib/dbconf.php';//数据库相关
require 'lib/3rd_lib/simple_html_dom.php';
//require 'for_debug/check_token-debug.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST')
    exit;
if (!check_username($_POST['username'])) {
    echo 3;
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
    echo 4;
    exit;
}
$cookie_arr = $db->query(
    "SELECT `idas_cookie`,`uestc_cookie`,`token` FROM `user_info` WHERE `student_number`='{$_POST["username"]}'"
)->fetch_all()[0];
if ($cookie_arr && $cookie_arr[2] == $_POST['token']) {//没找到||不一致
    //$cookie_str = $cookie_arr[0].';'.$cookie_arr[1];
    //$res = get(URL,$cookie_str);
    $res_body = get(URL,$cookie_arr[0].';'.$cookie_arr[1],true)['body'];
    $html = new simple_html_dom();
    $html->load($res_body);
    $title = $html->find('title',0);
    if($title->innertext()=='电子科技大学登录'){
        echo 2;
        exit;
    }
    else{
        echo 1;
        exit;
    }
    /*
    if(get(URL,$cookie_arr[0].';'.$cookie_arr[1])['status']=='200'){
        echo 1;
        exit;
    }
    else{
        echo 2;
        exit;
    }
    */
}
else{
    echo 3;
    exit;
}