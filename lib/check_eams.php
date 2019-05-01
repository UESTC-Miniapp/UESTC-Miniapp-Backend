<?php
/**
 * 用于验证eams的有效性
 */

require_once __DIR__ . '/url.php';
require_once __DIR__ . '/dbconf.php';
require_once __DIR__ . '/checkstr.php';

function check_eams($cookie_str)
{
    $res = get('http://eams.uestc.edu.cn/eams/home!submenus.action?menu.id=', $cookie_str, true)['body'];
    preg_match('/<title>(.*?)<\/title>/', $res, $titles);
    if ($titles[1] !== '')//空标题，十分魔性
        return false;
    else
        return true;
}