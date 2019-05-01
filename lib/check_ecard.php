<?php
/**
 * 确认ecard_cookie有效性
 */

require_once __DIR__ . '/url.php';

function check_ecard($ecard_cookie)
{
    $res = get('http://ecard.uestc.edu.cn/web/guest/personal', $ecard_cookie);
    if ($res['status'] == 200)
        return true;
    else
        return false;
}