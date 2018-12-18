<?php
/**
 * 登录
 * 使用POST，传递学号(username)和密码(passwd)
 * cookie使用JWT标准保存
 * JWT结构：
 * {
 * cookie:{
 *          idas: String,
 *          eams: String,
 *          ecard: String
 *      }
 * }
 */
header('Content-type: application/json');
require 'lib/url.php';
require 'lib/3rd_lib/simple_html_dom.php';
require 'lib/dbconf.php';
require 'lib/checkstr.php';
require 'lib/err_msg.php';
require 'lib/eams_login.php';
require 'lib/ecard_login.php';
require 'lib/jwt_parse.php';
require 'lib/exception.php';

require 'vendor/autoload.php';

function err($code, $s, $t)
{
    if (!is_int($code)) {
        return json_encode(array(
            'success' => false,
            'error_code' => 105,
            'error_msg' => err_msg(105, E_LOGIN),
            'token' => $t,
            'status' => $s
        ));
    }
    if ($code == 108 || $code == 109)
        return json_encode(array(
            'success' => true,
            'error_code' => $code,
            'error_msg' => err_msg($code, E_LOGIN),
            'status' => $s,
            'token' => $t
        ));
    return json_encode(array(
        'success' => false,
        'error_code' => $code,
        'error_msg' => err_msg($code, E_LOGIN),
        'status' => $s,
    ));

}

$status = array(
    'idas' => false,
    'eams' => false,
    'ecard' => false
);
$ret_token = '';
$token_json = [
    'cookie' => []
];

//log
//file_put_contents('log.php', date('c') . ',' . $_SERVER['REMOTE_ADDR'] . ',' . "login,\n", FILE_APPEND);
stdlog(",{$_SERVER['REMOTE_ADDR']},login");

try {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        throw new UMBException(206);
    }
    if (!(array_key_exists('username', $_POST) &&
        array_key_exists('passwd', $_POST))) {
        throw new UMBException(206);
    }
    if (array_key_exists('cap', $_POST)) {
        //验证码登录，需要token
        if (!array_key_exists('token', $_POST)) {
            throw new UMBException(206);
        }
    }

//检测用户输入
    if (!check_username($_POST['username'])) {
        throw new UMBException(204);
    }

//URL
    define('URL', 'http://idas.uestc.edu.cn/authserver/login');
    define('CapURL', 'http://idas.uestc.edu.cn/authserver/needCaptcha.html?username=' .
        $_POST['username'] . '&_=');
    define('CapIMGURL', 'http://idas.uestc.edu.cn/authserver/captcha.html');

    $response = get(URL);
    if ($response == null) {
        throw new UMBException(203);
    }

    $cookie_str = '';//提取cookie字符串
    if (array_key_exists('cap', $_POST)) {//携带验证码，提取cookie，同时验证token
        $query_res = jwt_decode($_POST['token']);
        $cookie_str = $query_res['cookie']['idas'];
    } else {//读取新的cookie
        foreach ($response['header']['Set-Cookie'] as $value) {
            $cookie_str = strpos($value, ';') ?
                $cookie_str . substr($value, 0, strpos($value, ';')) . ';' :
                $cookie_str . $value . ';';
        }
        $cookie_str = substr($cookie_str, 0, strlen($cookie_str) - 1);//去掉末尾的分号
    }

//确定是否需要验证码
    if (get(CapURL . (string)time() . '000')['body'] != "false\r\n") {
        //需要验证码
        if (!array_key_exists('cap', $_POST)) {//请求中不含验证码
            //cookie存入JWT，响应code=102,token,cap_img
            $token_json['cookie']['idas'] = $cookie_str;
            //响应
            echo jwt_encode($token_json);
            exit;
        }
    }

    //不知道需不需要，需要应该也能登录的吧

    //装载html，准备POST数据，shd挺稳定的，暂时不用正则了
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

    //带验证码登录，已经从数据库加载cookie_str，在$data中添加验证码
    if (array_key_exists('cap', $_POST))
        $data['captchaResponse'] = $_POST['cap'];

//发送登录请求
    $res = post(URL, $data, $cookie_str);

    if ($res['status'] == '302') {
        $status['idas'] = true;
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
            throw new Exception(err_msg(105, E_LOGIN), 105);
        }

        //生成token
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
                "`token`='{$token_hash}'" .
                //"`ecard_cookie` = '{$ecard_cookie}'," .
                //"`eams_cookie`='{$new_cookies['eams']}' " .//eams.uestc.edu.cn子域
                "WHERE `student_number`='{$_POST["username"]}'"
            );
        else
            $db->query(
                "INSERT INTO `user_info` (" .
                "`student_number`,`token`,`idas_cookie`,`uestc_cookie`" .
                //"`eams_cookie`,".
                //"`ecard_cookie`".
                ") VALUES ('{$_POST["username"]}','{$token_hash}','{$cookie_str}','{$iPlanetDirectoryPro}'" .
                //"'{$new_cookies['eams']}',".
                //"'{$ecard_cookie}'".
                ")"
            );
        $ret_token = $token;

        //eams登录
        $new_cookies = eams_login($cookie_str, $iPlanetDirectoryPro);
        if ($new_cookies == null) {
            throw new Exception(err_msg(108, E_LOGIN), 108);
        }
        $status['eams'] = true;
        if ($new_cookies['idas'] != '') {
            preg_replace('/JSESSIONID\_ids\d\=.*;/', $new_cookies['idas'] . ';', $cookie_str);//idas改变
        }
        if ($new_cookies['iplan'] != '') {
            $iPlanetDirectoryPro = $new_cookies['iplan'];
        }
        //数据库写入
        if ($db->query(
            "SELECT 1 FROM `user_info` WHERE `student_number`='{$_POST["username"]}' LIMIT 1")
            ->fetch_all())
            //存在，update
            $db->query(
                "UPDATE `user_info` SET " .
                "`eams_cookie`='{$new_cookies['eams']}' " .//eams.uestc.edu.cn子域
                "WHERE `student_number`='{$_POST["username"]}'"
            );
        else
            $db->query(
                "INSERT INTO `user_info` (" .
                "`eams_cookie`" .
                ") VALUES (" .
                "'{$new_cookies['eams']}'" .
                ")"
            );

        $ret_token = $token;

        if ($res['status'] != '302') {
            //登录失败
            if ($res['status'] == '200') {
                //登录失败，密码错误或者需要验证码，读取cpatchaDiv

                if (strpos($res['body'], 'id="msg"')) {//错误消息的提示
                    //$pos1 = strpos($res['body'], 'id="msg"');
                    //strpos($res['body'], '>', strpos($res['body'], 'id="msg"'));
                    //这里有机会的话就重写一下吧，略难看
                    $msg = substr($res['body'],
                        strpos($res['body'], '>', strpos($res['body'], 'id="msg"')) + 1,
                        strpos($res['body'], '</span>', strpos($res['body'], 'id="msg"')) -
                        strpos($res['body'], '>', strpos($res['body'], 'id="msg"')) - 1);


                    if ($msg == '您提供的用户名或者密码有误') {
                        throw new UMBException(204);
                    } elseif ($msg == '请输入验证码') {
                        throw new UMBException(104);
                    } elseif ($msg == '无效的验证码') {
                        throw new UMBException(104);
                    } else
                        throw new UMBException(202);
                } else
                    throw new UMBException(202);
            }
        }
    }
} catch
(UMBException $e) {
    if ($e->getCode() === 102)
        //验证码
        echo json_encode([
            'success' => true,
            'error_code' => $e->getCode(),
            'error_msg' => $e->getMessage(),
            //有点麻烦
        ]);
    else
        echo json_encode([
            'success' => false,
        ]);

} catch (Exception $e) {
    echo err($e->getCode(), $status, $ret_token);
}