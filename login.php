<?php
/**
 * 用于登录，登录之后cookie保存到服务器数据库用于爬之后的数据
 * 本文件的请求使用POST，传递学号(username)和密码(passwd)
 * 响应1或者2，1=成功/2=失败
 */

require 'lib/url.php';
require 'lib/3rd_lib/simple_html_dom.php';
require 'lib/dbconf.php';
require 'lib/checkstr.php';
require 'lib/err_msg.php';
require 'for_debug.php';//方便调试的时候使用
require 'lib/eams_login.php';

define('SALT', 'asjhujkdsnlkjsglkjvndlkKHSAHDNkndvdowl.swjNJKFi');//hash盐

function err($code)
{
    if (!is_int($code)) {
        return json_encode(array(
            'success' => false,
            'err_code' => 105,
            'error_msg' => err_msg(105, E_LOGIN)
        ));
    }
    return json_encode(array(
        'success' => false,
        'err_code' => $code,
        'error_msg' => err_msg($code, E_LOGIN)
    ));
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo err(106);
    exit;//结束
}
if (!(array_key_exists('username', $_POST) &&
    array_key_exists('passwd', $_POST))) {
    echo err(106);
    exit;
}
if (array_key_exists('cap', $_POST)) {
    if (!array_key_exists('token', $_POST)) {
        echo err(106);
        exit;
    }
}

//数据库连接
$db = new mysqli();
$db->connect(
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_NAME,
    DB_PORT);
if ($db->connect_errno) {
    //echo '数据库连接失败:' . (string)$db->connect_errno;
    echo err(105);
    exit;
}

//检测用户输入
if (!check_username($_POST['username'])) {
    echo err(103);
    exit;
}

//URL
define('URL', 'http://idas.uestc.edu.cn/authserver/login');
define('CapURL', 'http://idas.uestc.edu.cn/authserver/needCaptcha.html?username=' . $_POST['username'] . '&_=');
define('CapIMGURL', 'http://idas.uestc.edu.cn/authserver/captcha.html');

$response = get(URL);
$cookie_str = '';//提取cookie字符串
if (array_key_exists('cap', $_POST)) {//携带验证码，从数据库提取cookie
    $query_res = $db->query(
        "SELECT `token`,`idas_cookie` FROM `user_info` WHERE `student_number`='{$_POST["username"]}'"
    )->fetch_all();
    if ($query_res) {//有数据
        //验证token
        if (hash('sha256', $_POST['token']) == $query_res[0][0])
            $cookie_str = $query_res[0][1];
        else {
            echo err(107);
            exit;
        }
    } else {
        echo err(106);
        exit;
    }
} else {//读取新的cookie
    foreach ($response['header']['Set-Cookie'] as $value) {
        $cookie_str = strpos($value, ';') ?
            $cookie_str . substr($value, 0, strpos($value, ';')) . ';' :
            $cookie_str . $value . ';';
    }
    $cookie_str = substr($cookie_str, 0, strlen($cookie_str) - 1);//去掉末尾的分号
}

//确定是否需要验证码
if (get(CapURL . (string)time() . '000')['body'] != "false\n") {//需要验证码
    if (!array_key_exists('cap', $_POST)) {//请求中不含验证码
        //cookie存入数据库，响应code=102,token,cap_img
        $token = hash('sha256',
            $cookie_str . (string)time() . $_POST['username'] . SALT);//生成token
        $token_hash = hash('sha256', $token);
        //检测数据库是否存在此学号
        if ($db->query(
            "SELECT 1 FROM `user_info` WHERE `student_number`='{$_POST["username"]}' LIMIT 1")
            ->fetch_all())
            //存在，update
            $db->query(
                "UPDATE `user_info` SET `idas_cookie`='{$cookie_str}' WHERE `student_number`='{$_POST["username"]}'"
            );
        else
            $db->query(
                "INSERT INTO `user_info` (`student_number`,`token`,`idas_cookie`) VALUES ('{$_POST["username"]}','{$token_hash}','{$cookie_str}')"
            );
        //响应
        echo json_encode(array(
            'success' => false,
            'error_code' => 102,
            'error_msg' => 'require captcha',
            'content' => base64_encode(get(CapIMGURL, $cookie_str)['body']),
            'token' => $token
        ));
        exit;
    }
}

//不知道需不需要，需要应该也能登录的吧
$html = new simple_html_dom();
$html->load($response['body']);
$input = $html->find('input');//读取所有输入框
$data = array(
    'username' => $_POST['username'],
    'password' => $_POST['passwd'],
    $input[2]->name => $input[2]->value,
    $input[3]->name => $input[3]->value,
    $input[4]->name => $input[4]->value,
    $input[5]->name => $input[5]->value,
    $input[6]->name => $input[6]->value
);

if (array_key_exists('cap', $_POST))//带验证码登录，已经从数据库加载cookie_str，在$data中添加验证码
    $data['captchaResponse'] = $_POST['content'];

//发送登录请求
$res = post(URL, $data, $cookie_str);

if ($res['status'] == '302') {
    //print_r($res['cookie']);
    //登录成功，继续登录eams，cookie写入数据库
    $iPlanetDirectoryPro = '';
    foreach ($res['cookie'] as $key => $value) {//提取cookie字符串
        //iPlanetDirectoryPro单独处理
        if ($key == 'iPlanetDirectoryPro') {
            $iPlanetDirectoryPro = 'iPlanetDirectoryPro' . '=' . $value;
            continue;
        }
        $cookie_str = $key . '=' . $value . ';' . $cookie_str;
    }
    //$new_loc = $res['head']['Location'];//暂时不需要处理302跳转
    if (!$iPlanetDirectoryPro) {//如果没有这个cookie，估计是有问题的，为了稳定性
        echo err(105);
        exit;
    }

    //eams登录
    $new_cookies = eams_login($cookie_str, $iPlanetDirectoryPro);
    if ($new_cookies == null) {
        echo err(105);
        exit;
    }
    if ($new_cookies['idas'] != '') {
        preg_replace('/JSESSIONID\_ids\d\=.*;/', $new_cookies['idas'] . ';', $cookie_str);//idas改变
    }
    if ($new_cookies['iplan'] != '') {
        $iPlanetDirectoryPro = $new_cookies['iplan'];
    }

    $token = hash('sha256',
        $cookie_str . (string)time() . $_POST['username'] . SALT);//生成token
    $token_hash = hash('sha256', $token);

    //检测数据库是否存在此学号
    if ($db->query(
        "SELECT 1 FROM `user_info` WHERE `student_number`='{$_POST["username"]}' LIMIT 1")
        ->fetch_all())
        //存在，update
        $db->query(
            "UPDATE `user_info` SET " .
            "`idas_cookie`='{$cookie_str}'," .//idas.uestc.edu.cn子域
            "`uestc_cookie`='{$iPlanetDirectoryPro}'," .//uestc.edu.cn主域
            "`token`='{$token}'," .
            "`eams_cookie`='{$new_cookies['eams']}' " .//eams.uestc.edu.cn子域
            "WHERE `student_number`='{$_POST["username"]}'"
        );
    else
        $db->query(
            "INSERT INTO `user_info` (`student_number`,`token`,`idas_cookie`,`uestc_cookie`,`eams_cookie`) VALUES ('{$_POST["username"]}','{$token_hash}','{$cookie_str}','{$iPlanetDirectoryPro}','{$new_cookies['eams']}')"
        );

    //echo $token;
    echo json_encode(array(
        'success' => true,
        'error_code' => null,
        'error_msg' => '',
        'token' => $token
    ));
} elseif ($res['status'] == '200') {//登录失败，密码错误或者需要验证码，读取cpatchaDiv
    if (strpos($res['body'], 'id="msg"')) {//错误消息的提示
        //$pos1 = strpos($res['body'], 'id="msg"');
        //strpos($res['body'], '>', strpos($res['body'], 'id="msg"'));
        //这里有机会的话就重写一下吧，略难看
        $msg = substr($res['body'],
            strpos($res['body'], '>', strpos($res['body'], 'id="msg"')) + 1,
            strpos($res['body'], '</span>', strpos($res['body'], 'id="msg"')) -
            strpos($res['body'], '>', strpos($res['body'], 'id="msg"')) - 1);
        if ($msg == '您提供的用户名或者密码有误') {
            echo err(103);
            exit;
        } elseif ($msg == '请输入验证码') {
            echo err(104);
            exit;
        } elseif ($msg == '无效的验证码') {
            echo err(104);
            exit;
        }
    } else
        echo err(105);
}