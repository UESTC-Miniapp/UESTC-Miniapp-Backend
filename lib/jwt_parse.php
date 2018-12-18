<?php
/**
 * jwt解析
 */

/**
 * @param string
 * @author hzy
 * @return bool
 * 验证token的签名
 */
function jwt_check(string $token)
{
    if (strlen($token) <= 65)
        return false;
    $sign = substr($token, -64);
    $token = substr($token, 0, -64);
    //验证签名
    if (hash_hmac('sha256', $token, SECRET) !== $sign)
        return false;
    return true;
}

/**
 * @param string
 * @throws UMBException
 * @author hzy
 * @return mixed
 * 使用base64编码，使用HMAC(sha256)签名
 */
function jwt_decode(string $token)
{
    if (jwt_check($token) === false)
        throw new UMBException(201);
    //解析JWT
    $token = base64_decode($token, true);
    if ($token === false)
        throw new UMBException(201);
    $token_arr = json_decode($token);
    if ($token_arr == null)
        throw new UMBException(201);
    return $token_arr;
}

/**
 * @param array
 * @author hzy
 * @return string
 * 编码token+签名
 */
function jwt_encode(array $arr)
{
    $token = base64_encode(json_encode($arr));
    return $token . hash_hmac('sha256', $token, SECRET);
}