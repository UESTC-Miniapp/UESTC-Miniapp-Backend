<?php
/*
 * ©hzy
 * 用于简单的发送http请求，支持GET/POST，
 * $data参数为Array
 * 使用Cookie时可以添加到第三个参数，使用字符串，多个cookie使用分号连接
 * 返回值为数组或bool，包括head,body,cookie,status
 * 使用Chrome的User-Agent
 * 没有使用cookieJar所以不支持多域名，自动跳转不建议开启
 */

/**
 * @param string $url
 * @param array $data
 * @param string $cookie_str
 * @param bool $auto_follow
 * @return array|null
 */
function post(string $url, array $data = array(), string $cookie_str = '', bool $auto_follow = false)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);//method=post
    if ($auto_follow) {
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);//重定向自动设定referer
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//自动重定向
    }
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);//10s超时
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));//post data
    curl_setopt($ch, CURLOPT_URL, $url);//url
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//return value
    curl_setopt($ch, CURLOPT_HEADER, 1);    //response header
    curl_setopt($ch, CURLOPT_NOBODY, 0); //response body
    curl_setopt($ch, CURLOPT_COOKIE, $cookie_str);//cookie
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36');
    //curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
    $output = curl_exec($ch);
    if (!$output) {
        return null;
    }
    if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != '404') {//之类的，待修改
        $header_str = substr($output, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        $header_arr = explode("\n", $header_str);
        $header_str = substr($header_str, strpos($header_str, "\r\n"));
        $header = array();//header中的key-value读取到array
        $cookie_array = array();
        $pos3 = 0;
        //$len = strlen($header_str);
        while (true) {
            if (strlen($header_str) <= $pos3 + 4)//分隔为\r\n\r\n
                break;
            $pos1 = $pos3 + 2;
            $pos2 = strpos($header_str, ': ', $pos1);
            $pos3 = strpos($header_str, "\r\n", $pos2);
            $key = substr($header_str, $pos1, $pos2 - $pos1);
            $value = substr($header_str, $pos2 + 2, $pos3 - $pos2 - 2);

            if ($key == 'Set-Cookie' or //对cookie单独处理
                $key == 'SET-COOKIE') {//学校的坑爹操作，临时修改
                $key = 'Set-Cookie';//临时修改

                $cookie_key = substr($value, 0, strpos($value, '='));//分离cookie的key
                $cookie_value = substr($value, strpos($value, '=') + 1);
                if (strpos($cookie_value, ';') !== false)//只保留value部分
                {
                    $cookie_value = substr(
                        $cookie_value, 0, strpos($cookie_value, ';'));
                }
                $cookie_array[$cookie_key] = $cookie_value;
                /*
                //只保留第一个分号之前的
                if (strpos($value, ';'))
                    $value = substr($value, 0, strpos($value, ';'));
                $header['Set-Cookie'] = $header['Set-Cookie'] ? $header['Set-Cookie'] . ';' . $value : $value;
                continue;*/
            }
            if (array_key_exists($key, $header))
                //$header[$key]->append($value);
                //array_push($header[$key],$value);
                $header[$key][] = $value;
            else
                $header[$key] = array($value);
            //$header[$key] = new ArrayObject(array($value));
            //$header[substr($header_str,$pos1,$pos2-$pos1)]=substr($header_str,$pos2+2,$pos3-$pos2-2);
        }
        $tmp = array(
            'header' => $header,
            'body' => substr($output, curl_getinfo($ch, CURLINFO_HEADER_SIZE)),
            'cookie' => $cookie_array,
            'status' => curl_getinfo($ch, CURLINFO_HTTP_CODE)
        );
        curl_close($ch);
        return $tmp;
    }
    curl_close($ch);
    return null;
}

/**
 * @param string $url
 * @param string $cookie_str
 * @param bool $auto_follow
 * @return array|null
 */
function get(string $url, string $cookie_str = '', bool $auto_follow = false)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if ($auto_follow) {
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);//重定向自动设定referer
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//自动重定向
        curl_setopt($ch, CURLOPT_MAXREDIRS, 30);//重定向30次！
    }
    //curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_NOBODY, 0);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie_str);//cookie
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36');


    $output = curl_exec($ch);
    if (!$output) {
        return null;
    }

    if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != '404') {
        $header_str = substr($output, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        $header_str = preg_replace("/HTTP\/1\.\d\s[1-5]\d\d\s\w{1,6}\r\n/", '', $header_str);

        $header = array();//header中的key-value读取到array
        $cookie_array = array();
        $pos3 = 0;
        //$len = strlen($header_str);
        while (true) {
            if (strlen($header_str) <= $pos3 + 4)//分隔为\r\n\r\n
                break;
            $pos1 = $pos3 + 2;
            $pos2 = strpos($header_str, ': ', $pos1);
            $pos3 = strpos($header_str, "\r\n", $pos2);
            $key = substr($header_str, $pos1, $pos2 - $pos1);
            $value = substr($header_str, $pos2 + 2, $pos3 - $pos2 - 2);

            if ($key == 'Set-Cookie') {//对cookie单独处理
                $cookie_key = substr($value, 0, strpos($value, '='));//分离cookie的key
                $cookie_value = substr($value, strpos($value, '=') + 1);
                if (strpos($cookie_value, ';'))//只保留value部分
                    $cookie_value = substr($cookie_value, 0, strpos($cookie_value, ';'));
                $cookie_array[$cookie_key] = $cookie_value;
                /*
                //只保留第一个分号之前的
                if(strpos($value,';'))
                    $value = substr($value,0,strpos($value,';'));
                $header['Set-Cookie']=$header['Set-Cookie']?$header['Set-Cookie'].';'.$value:$value;
                continue;*/
            }
            if (array_key_exists($key, $header))
                //$header[$key]->append($value);
                //array_push($header[$key],$value);
                $header[$key][] = $value;
            else
                $header[$key] = array($value);
            //$header[$key] = new ArrayObject(array($value));
            //$header[substr($header_str,$pos1,$pos2-$pos1)]=substr($header_str,$pos2+2,$pos3-$pos2-2);
        }
        $tmp = array(
            'header' => $header,
            'body' => substr($output, curl_getinfo($ch, CURLINFO_HEADER_SIZE)),
            'cookie' => $cookie_array,
            'status' => curl_getinfo($ch, CURLINFO_HTTP_CODE)
        );
        curl_close($ch);
        return $tmp;
    }
    curl_close($ch);
    return null;
}