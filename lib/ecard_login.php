<?php
/**
 * 一卡通登录
 */
if (!require_once 'url.php')
    require 'url.php';
if(!require_once 'exception.php')
    require 'exception.php';

//一般走到这一步的话，用户名和密码是不会有问题的
/**
 * @param $u
 * @param $p
 * @return string
 * @throws UMBException
 * 一卡通登录
 */
function ecard_login($u, $p)
{
    $res = get('http://ecard.uestc.edu.cn/');
    if ($res['status'] != 200) //一卡通网站不可用
        throw new UMBException(109);
    if ($res['cookie']['COOKIE_SUPPORT'] != 'true' || !key_exists('JSESSIONID', $res['cookie']))
        throw new UMBException(109);
    //preg_match_all('/<input.*?name=\"(.*?)\".value\=\"(.*?)\"/', $res['body'], $input_arr);
    $login_data = array(
        '_58_login_type' => '_58_login_type',
        '_58_login' => $u,
        '_58_password' => $p
    );
    $res2 = post(
        'http://ecard.uestc.edu.cn/c/portal/login',
        $login_data,
        'JSESSIONID=' . $res['cookie']['JSESSIONID='] . ';COOKIE_SUPPORT=true');
    if ($res2['status'] != 302)
        throw new UMBException(109);
    if ($res2['header']['Location'][0] != 'http://ecard.uestc.edu.cn/web/guest/personal')
        throw new UMBException(109);
    $cookie_str = 'JSESSIONID=' . $res2['cookie']['JSESSIONID'] . ';COOKIE_SUPPORT=true;GUEST_LANGUAGE_ID=zh_CN';
    $res3 = get('http://ecard.uestc.edu.cn/web/guest/personal', $cookie_str);
    if ($res3['status'] != 200)
        throw new UMBException(109);
    return $cookie_str;
}