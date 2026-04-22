<?php

$accessKey = "747FE502E08AFE3A2DDA2BF0956FD70B";
$secretKey = "8507770E176498652C9DA73041DA846BB3A203E4EC73009E6F58754CE17EBD20";

// -------- AES 加密函数 --------
function aesEncrypt(array $data, string $secretKey): string
{
    $json = json_encode($data);

    $key = substr($secretKey, 0, 16);  // 16字节
    $iv  = substr($secretKey, 16, 16); // 16字节

    $enc = openssl_encrypt($json, 'AES-128-CBC', $key, 0, $iv);
    return base64_encode($enc);
}

// -------- HMAC-SHA256 签名 --------
function calcSign(array $params, string $secretKey): string
{
    $msg = $params['access_key']
        . $params['timestamp']
        . $params['nonce']
        . $params['data'];

    return strtoupper(hash_hmac('sha256', $msg, $secretKey));
}
/**
 * 发送 POST 请求（可复用）
 * @param string $url
 * @param array|string $data  支持数组或JSON字符串
 * @param array $headers      例如 ['Content-Type: application/json']
 * @return string|null
 */
function httpPost(string $url, $data = [], array $headers = []): string|null
{
    $ch = curl_init();

    // 如果传入的是数组 → 自动转为 x-www-form-urlencoded
    if (is_array($data)) {
        $postFields = http_build_query($data);
    } else {
        // 认为是 JSON 或字符串
        $postFields = $data;
    }

    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,              // 超时保护
        CURLOPT_HTTPHEADER     => $headers
    ]);

    $response = curl_exec($ch);
    $errno    = curl_errno($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($errno) {
        return null;
    }

    return $response;
}

// -------- 业务数据 --------
$business = [
    'account_number' => ["1900292929"],
    'lessee_name'   => '测试商户1',
    'lessee_code'   => 'SH123',
    'body'   => '验证码340399 请不要告诉其他人',
    'send_type'   => 0, //0:短信 1:彩信2:邮件
    'mms_url'   => '',
    'subject'   => '',
];

// -------- 构建参数 --------
$params = [
    'access_key' => $accessKey,
    'timestamp'  => intval(microtime(true) * 1000),
    'nonce'      => bin2hex(random_bytes(8)),
    'data'       => aesEncrypt($business, $secretKey),
];
$params['sign'] = calcSign($params, $secretKey);
print_r($params);
// -------- 发送请求 --------
$url = "http://127.0.0.1:5500/api/clients/sendSms";
$result = httpPost($url, $params);
echo $result;
