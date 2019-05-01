<?php
/**
 * 用于登录eams，获取cookie之后保存到数据库
 * 理论上应该不是允许请求的
 * 需要idas的cookie，用于登录统一验证
 */

require_once __DIR__ . '/url.php';
require_once __DIR__ . '/dbconf.php';
require_once __DIR__ . '/checkstr.php';

function eams_login($idas_cookie, $uestc_cookie)
{
    $res = get('http://eams.uestc.edu.cn/eams/home!submenus.action');//获取原生cookie:sto-id-20480
    if ($res['status'] != 302)
        return null;
    //新的url，理论上来说应该是
    //http://idas.uestc.edu.cn/authserver/login?service=http%3A%2F%2Feams.uestc.edu.cn%2Feams%2Fhome%21submenus.action%3Fmenu.id%3D
    //不过还是为了以防万一吧
    $url2 = $res['header']['Location'][0];
    //url2修改为https
    $url2 = preg_replace('/^http/', 'https', $url2, 1);
    //登录这里的时候使用参数中的idas_cookie
    $res2 = get($url2, $idas_cookie . ';' . $uestc_cookie);
    if ($res2['status'] != 302)
        return null;
    $url3 = $res2['header']['Location'][0];//带ticket参数的那个
    $cookie3 = 'sto-id-20480=' . $res['cookie']['sto-id-20480'] . ';' . $uestc_cookie;
    $res3 = get($url3, $cookie3);
    if ($res3['status'] != 302)
        return null;
    $url4 = $res3['header']['Location'][0];
    //从url4中已经可以读取到JSESSIONID了
    preg_match('/jsessionid=(.*)$/', $url4, $jsid_arr);
    $cookie_str = 'sto-id-20480=' . $res['cookie']['sto-id-20480'] . ';' .
        str_replace('jsessionid', 'JSESSIONID', $jsid_arr[0]);
    //激活（？）
    $res4 = get($url4, $cookie_str);
    if ($res4['status'] != 200)
        return null;
    $idas_js_cookie = '';
    $iplan = '';

    if (array_key_exists('Set-Cookie', $res2['header'])) {
        $sum = '';
        foreach ($res2['header']['Set-Cookie'] as $value)//先合并
            $sum = $sum . $value;

        if (strpos($sum, 'iPlanetDirectoryPro')) {//判断是否有iplan
            $iplan = 'iPlanetDirectoryPro=' . $res2['cookie']['iPlanetDirectoryPro'];
        }
        if (strpos($sum, 'JSESSIONID')) {//判断是否有JSESSIONID_idsx
            preg_match('/JSESSIONID\_ids\d\=.*;/', $sum, $result_arr);
            $idas_js_cookie = substr($result_arr[0], 0, strlen($result_arr[0]) - 1);
        }
    }

    return array(
        'eams' => $cookie_str,
        'idas' => $idas_js_cookie,
        'iplan' => $iplan
    );
}