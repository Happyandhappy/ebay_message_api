<?php

    $url = "https://www.amazon.com/dp/B00XTXY0JW";
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

    $headers = array();
    $headers[] = "Origin: https://www.amazon.com";
    $headers[] = "Accept-Encoding: gzip, deflate, br";
    $headers[] = "Accept-Language: en-US,en;q=0.9";
    $headers[] = "User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
    $headers[] = "Content-Type: application/x-www-form-urlencoded; charset=UTF-8";
    $headers[] = "Accept: */*";
    $headers[] = "X-Requested-With: XMLHttpRequest";
    $headers[] = "Connection: keep-alive";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $source = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close ($ch);

    echo $source;
?>