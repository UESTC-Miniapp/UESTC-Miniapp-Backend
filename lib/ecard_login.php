<?php
/**
 * 一卡通登录
 */
if (!require_once 'url.php')
    require 'url.php';
if (!require_once 'exception.php')
    require 'exception.php';

//一般走到这一步的话，用户名和密码是不会有问题的
//修改登录方案，从统一验证跳转过去，使用Guzzel
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Cookie\CookieJar;

/**
 * @param $u
 * @param $p
 * @param $token_json
 * @return string
 * @throws UMBException
 * 一卡通登录
 */
function ecard_login(string $u, string $p, array $token_json)
{

    try {
        $client = new Client(['cookies' => true]);
        //检测一卡通网站
        $res = $client->request('GET', 'http://ecard.uestc.edu.cn');
        if ($res->getStatusCode() !== 200)
            throw new UMBException(109);
        $cookie_arr = ($client->getConfig('cookies'))->toArray();
        if (!($cookie_arr[1]['Name'] === 'COOKIE_SUPPORT' && $cookie_arr[1]['Value'] === 'true'))
            throw new UMBException(109);
        $idas_str = $token_json['cookie']['idas'];
        $idas_arr = explode(';', $idas_str);
        $idas_arr_key = [];
        foreach ($idas_arr as $value) {
            $idas_arr_key[explode('=', $value)[0]] = explode('=', $value)[1];
        }
        $idas_jar = CookieJar::fromArray($idas_arr_key, 'idas.uestc.edu.cn');

        $res = $client->request('GET',
            'http://ecard.uestc.edu.cn/caslogin.jsp', [
                'cookies' => $idas_jar,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36'
                ]
            ]);
        $res = $client->request('GET',
            'http://ecard.uestc.edu.cn/caslogin.jsp', [
                'cookies' => $idas_jar,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36'
                ]
            ]);
        $res = $client->request('GET',
            'http://ecard.uestc.edu.cn/c/portal/login', [
                'cookies' => $idas_jar,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36'
                ]
            ]);
        preg_match('/<title>(.*?)<\/title>/', (string)$res->getBody(), $title_arr);
        if ($title_arr[1] !== '我的账户 - 一卡通服务门户')
            throw new UMBException(202);
    } catch (GuzzleException $e) {
        throw new UMBException(109);
    }
    $cookie_str = '';
    foreach ($idas_jar->toArray() as $value) {
        if ($value['Domain'] === 'ecard.uestc.edu.cn')
            $cookie_str .= $value['Name'] . '=' . $value['Value'] . ';';
    }
    return substr($cookie_str, 0, -1);
}