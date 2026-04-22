<?php

namespace app\modules\myclass;


use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Exception;
use support\Request;

class JwtService
{
    private $secretKey;
    private $algorithm;

    public function __construct()
    {
        // 从配置获取密钥，建议在config/plugin/webman/app/app.php中配置
        $this->secretKey = config('app.jwt_secret', 'fallback-weak-key-set-proper-in-config');
        $this->algorithm = 'HS256';

        if (strlen($this->secretKey) < 32) {
            throw new Exception('JWT密钥强度不足，请设置至少32位的随机字符串');
        }
    }

    /**
     * 生成用户登录Token（绑定IP）
     */
    public function generateToken($data, $clientIp, $expireHours = 72)
    {

        $payload = [
            'iss' => config('app.app_url', 'localhost'), // 签发者
            'aud' => config('app.app_url', 'localhost'), // 接收方
            'iat' => time(),                      // 签发时间
            'exp' => time() + ($expireHours * 3600), // 过期时间
            'sub' => $data['id']??0,                     // 主题（用户ID）
            'ip'  => $clientIp,                   // 绑定客户端IP
            'jti' => bin2hex(random_bytes(16)),   // Token唯一标识
            'user' => $data,
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * 验证Token并返回用户数据（验证IP绑定）
     */
    public function validateToken($token, Request $request)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            $payload = (array) $decoded;

            // 验证IP绑定 - 核心安全校验
            // $currentIp = $this->getClientIp($request);
            // $tokenIp = $payload['ip'] ?? '';
            // if ($tokenIp !== $currentIp) {
            //     return [
            //         'success' => false,
            //         'code' => token_ip_mismatch,
            //         'message' => 'token_ip_mismatch'
            //     ];
            // }

            return [
                'success' => true,
                'data' => $payload,
                'user_id' => $payload['sub']
            ];
        } catch (ExpiredException $e) {
            return [
                'success' => false,
                'code' => token_expired,
                'message' => 'token_expired'
            ];
        } catch (SignatureInvalidException $e) {
            return [
                'success' => false,
                'code' => token_invalid,
                'message' => 'token_invalid'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'code' => token_verification_failed,
                'message' => trans('token_verification_failed') . ': ' . $e->getMessage()
            ];
        }
    }

    /**
     * 从Authorization头中提取Token
     */
    public function getTokenFromHeader(Request $request)
    {
        $authHeader = $request->header('authorization');

        if (empty($authHeader)) {
            return null;
        }

        // 支持 "Bearer {token}" 格式
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        return trim($authHeader);
    }

    /**
     * 获取客户端真实IP（处理代理情况）
     */
    private function getClientIp(Request $request)
    {
        $ip = $request->getRealIp();

        // 如果有多个IP（如代理情况），取第一个
        if (strpos($ip, ',') !== false) {
            $ips = explode(',', $ip);
            $ip = trim($ips[0]);
        }

        return $ip;
    }

    /**
     * 刷新Token（保持IP绑定）
     */
    public function refreshToken($oldToken, Request $request, $expireHours = 72)
    {
        // 先验证旧Token
        $validation = $this->validateToken($oldToken, $request);

        if (!$validation['success']) {
            return [
                'success' => false,
                'error' => $validation['error'],
                'message' => $validation['message']
            ];
        }

        $payload = $validation['data'];

        // 生成新Token（保持相同的IP绑定）
        $newToken = $this->generateToken(
            $payload,
            $payload['ip']??'',
            $expireHours
        );

        return [
            'success' => true,
            'token' => $newToken,
            'expires_in' => $expireHours * 3600
        ];
    }
}
