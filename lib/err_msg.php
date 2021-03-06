<?php
/**
 * 错误信息
 */
define('E_LOGIN', 1);
define('E_CHECK', 2);
define('E_GRADE', 3);
define('E_EXAM', 4);
define('E_TT', 5);
define('E_PERSON', 6);
define('E_GEN', 7);

function err_msg($code, $type)
{
    switch ($type) {
        case 1:
            $err_msg = array(
                //102=>'require captcha',
                103 => 'wrong username or password',
                104 => 'wrong captcha',
                105 => 'unknown error',
                106 => 'bad request',
                107 => 'wrong token',
                108 => 'eams failed',
                109 => 'ecard failed',
                110 => 'idas failed'
            );
            break;
        case 2:
            $err_msg = array(
                201 => 'wrong token',
                202 => 'unknown error',
                203 => 'bad request'
            );
            break;
        case 3:
        case 4:
        case 5:
        case 6:
        case 7:
            $err_msg = array(
                201 => 'wrong token',
                202 => 'unknown error',
                203 => 'bad request'
            );
            break;
        default:
            return 0;
            break;
    }
    return $err_msg[$code];
}