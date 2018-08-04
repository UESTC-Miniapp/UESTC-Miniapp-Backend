<?php
/**
 * 用于验证eams的有效性
 */

if (!require_once 'url.php')
    require 'url.php';
if (!require_once '3rd_lib/simple_html_dom.php')
    require '3rd_lib/simple_html_dom.php';
if (!require_once 'dbconf.php')
    require 'dbconf.php';
if (!require_once 'checkstr.php')
    require 'checkstr.php';

function check_eams($cookie_str)
{
    $res = get('http://eams.uestc.edu.cn/eams/home!submenus.action?menu.id=', $cookie_str, true)['body'];
    preg_match('/<title>(.*?)<\/title>/', $res, $titles);
    if ($titles[1] == '电子科技大学登录')
        return false;
    else
        return true;
}