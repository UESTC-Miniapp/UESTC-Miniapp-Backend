<?php
/**
 * Created by PhpStorm.
 * User: hzy
 * Date: 2018/7/28
 * Time: 11:36
 */

function check_username($username){
    if(preg_match('/^\d{13}$/',$username))
        return true;
    else
        return false;
}
