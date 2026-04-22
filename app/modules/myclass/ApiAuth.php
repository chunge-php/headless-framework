<?php

namespace app\modules\myclass;

use support\Db;

class ApiAuth
{
    /** 🚀 创建客户：每个客户只允许一对 AK/SK */
    public static function createClient(int $uid): array
    {
        $accessKey = strtoupper(bin2hex(random_bytes(16))); // 32位
        $secretKey = strtoupper(bin2hex(random_bytes(32))); // 64位
        $now = date('Y-m-d H:i:s');
        Db::table('api_clients')->insert([
            'uid'        => $uid,
            'access_key' => $accessKey,
            'secret_key' => $secretKey,
            'status'     => 1,
            'created_at' => $now,
        ]);

        return [
            'access_key' => $accessKey,
            'secret_key' => $secretKey,
            'created_at' => $now
        ];
    }

    /** 🚀 根据 access_key 查客户 */
    public static function findByAccessKey(string $accessKey): ?array
    {
        $row = Db::table('api_clients')
            ->where('access_key', $accessKey)
            ->where('status', 1)
            ->first();

        return $row ? (array)$row : null;
    }

    /** 🔐 客户端生成基础参数 */
    public static function buildParams(string $accessKey): array
    {
        return [
            'access_key' => $accessKey,
            'timestamp'  => intval(microtime(true) * 1000),
            'nonce'      => bin2hex(random_bytes(8))
        ];
    }


    /** 🔐 AES-128-CBC 加密（兼容所有语言） */
    public static function aesEncrypt(array $data, string $secretKey): string
    {
        $json = json_encode($data);

        $key = substr($secretKey, 0, 16);  // 16字节
        $iv  = substr($secretKey, 16, 16); // 16字节

        $enc = openssl_encrypt($json, 'AES-128-CBC', $key, 0, $iv);
        return base64_encode($enc);
    }

    /** 🔐 AES 解密 */
    public static function aesDecrypt(string $encrypted, string $secretKey): ?array
    {
        $key = substr($secretKey, 0, 16);
        $iv  = substr($secretKey, 16, 16);

        $json = openssl_decrypt(base64_decode($encrypted), 'AES-128-CBC', $key, 0, $iv);
        return $json ? json_decode($json, true) : null;
    }

    /**
     * 🔐 HMAC-SHA256 统一签名算法（全语言可用）
     * sign = HMAC-SHA256(access_key + timestamp + nonce + data, secret_key)
     */
    public static function calcSign(array $params, string $secretKey): string
    {
        $msg = $params['access_key']
             . $params['timestamp']
             . $params['nonce']
             . $params['data'];

        return strtoupper(hash_hmac('sha256', $msg, $secretKey));
    }

    /** 🔐 服务端统一验证 + 解密业务 */
    public static function verifyAndDecrypt(array $params): array
    {
        // 检查必要字段
        foreach (['access_key', 'timestamp', 'nonce', 'data', 'sign'] as $key) {
            if (empty($params[$key])) {
                return ['success'=>false, 'msg'=>"missing $key"];
            }
        }

        $client = self::findByAccessKey($params['access_key']);
        if (!$client) {
            return ['success'=>false, 'msg'=>'invalid access_key'];
        }
        $secretKey = $client['secret_key'];
        $uid = $client['uid'];

        // 时间过期：10分钟
        if (time()*1000 - $params['timestamp'] > 600000) {
            return ['success'=>false, 'msg'=>'timestamp expired'];
        }

        // 验签
        $sign = self::calcSign($params, $secretKey);
        if ($sign !== $params['sign']) {
            return ['success'=>false, 'msg'=>'invalid sign'];
        }

        // 解密业务 data
        $data = self::aesDecrypt($params['data'], $secretKey);
        if ($data === null) {
            return ['success'=>false, 'msg'=>'invalid data'];
        }

        return ['success' => true, 'data' => $data,'uid'=>$uid]; // 成功
    }
}
