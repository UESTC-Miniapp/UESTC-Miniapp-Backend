<?php
/**
 * 异常
 */

if (!require_once 'dbconf.php')
    require 'dbconf.php';

//标准日志
function stdlog(string $log_msg)
{
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    //数据库连接失败，算了
    if ($db->errno) {
        return false;
    }
    $log_msg = $db->real_escape_string($log_msg);
    $db->query("INSERT INTO `stdlog` (`msg`) VALUES ('{$log_msg}')");
    return true;
}

//异常日志
function errlog(string $log_msg, int $err_code)
{
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    //数据库连接失败，算了
    if ($db->errno) {
        return false;
    }
    $log_msg = $db->real_escape_string($log_msg);
    $db->query("INSERT INTO `errlog` (`msg`,`err_code`) VALUES ('{$log_msg}',{$err_code})");
    return true;
}

class UMBException extends Exception
{
    public static $msg = [
        201 => 'Wrong token',
        202 => 'Unknow error',
        203 => 'eams failed',
        204 => 'Wrong student number or password',
        205 => 'Database failed',
        206 => 'Bad request',
        102 => 'Need captcha',
        104 => 'Wrong captcha',
        108 => 'eams failed',
        109 => 'ecard failed',
        110 => 'idas failed'

    ];

    public function __construct(int $code = 0, Throwable $previous = null)
    {
        $message = UMBException::$msg[$code];
        errlog($message, $code);
        parent::__construct($message, $code, $previous);
    }
}