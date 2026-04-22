<?php

namespace app\modules\myclass;


use Google\Client;
use Google\Service\Oauth2;

class GoogleAuthService
{
    protected $client;
    protected $config;

    public function __construct()
    {
        $this->config = config('google');

        $this->client = new Client();
        $this->client->setClientId($this->config['client_id']);
        $this->client->setClientSecret($this->config['client_secret']);
        $this->client->setRedirectUri($this->config['redirect_uri']);
        $this->client->addScope('email');
        $this->client->addScope('profile');
        $this->client->setAccessType('offline'); // 获取刷新令牌
        $this->client->setPrompt('consent'); // 强制每次显示授权页面
    }

    /**
     * 获取授权URL
     * @param string|null $state 可选的状态参数
     * @return string
     */
    public function getAuthUrl(string $state = null): string
    {
        if ($state) {
            $this->client->setState($state);
        }
        return $this->client->createAuthUrl();
    }

    /**
     * 使用授权码获取用户信息
     * @param string $code
     * @return array
     * @throws \Exception
     */
    public function getUserInfo(string $code): array
    {
        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                throw new \Exception($token['error_description'] ?? $token['error']);
            }

            $this->client->setAccessToken($token);
            $oauth = new Oauth2($this->client);
            $userInfo = $oauth->userinfo->get();
            return [
                'id' => $userInfo->id,
                'name' => $userInfo->name,
                'first_name' => $userInfo->given_name??'',
                'last_name' => $userInfo->family_name??'',
                'email' => $userInfo->email,
                'picture' => $userInfo->picture,
                'verified_email' => $userInfo->verifiedEmail,
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'expires_in' => $token['expires_in'] ?? null,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Google OAuth failed: ' . $e->getMessage());
        }
    }

    /**
     * 使用刷新令牌获取新的访问令牌
     * @param string $refreshToken
     * @return array
     * @throws \Exception
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        try {
            $this->client->refreshToken($refreshToken);
            return $this->client->getAccessToken();
        } catch (\Exception $e) {
            throw new \Exception('Failed to refresh token: ' . $e->getMessage());
        }
    }
}
