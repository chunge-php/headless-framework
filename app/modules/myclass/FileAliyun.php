<?php

namespace app\modules\myclass;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Sts\Sts;
use Webman\Config;

class FileAliyun
{

    private $config;
    public function  __construct()
    {
        $uploadConfig = Config::get('upload');
        $this->config = $uploadConfig['aliyun'] ?? [];
    }
    public function getSignature()
    {
        $expire_time = 3600; // 过期时间，单位为秒
        $upload_dir = 'user-dir'; // 上传文件的前缀
        // 处理获取POST签名的请求
        $secretId  = $this->config['access_key_id'] ?? '';
        $secretKey = $this->config['access_key_secret'] ?? '';
        $endpoint    = $this->config['endpoint'] ?? '';
        $role_ran    = $this->config['role_ran'] ?? '';
        $host    = $this->config['url_prefix'] ?? '';

      try{
        AlibabaCloud::accessKeyClient($secretId, $secretKey)
        ->regionId($endpoint)
        ->asDefaultClient();
      }catch(\Exception $e){
        debugMessage($e->getMessage(),'阿里云签名失败');

      }

        // 创建STS请求。
        $request = Sts::v20150401()->assumeRole();
        // 发起STS请求并获取结果。
        // 将<YOUR_ROLE_SESSION_NAME>设置为自定义的会话名称，例如oss-role-session。
        // 将<YOUR_ROLE_ARN>替换为拥有上传文件到指定OSS Bucket权限的RAM角色的ARN。
        $result = $request
            ->withRoleSessionName('oss-role-session')
            ->withDurationSeconds($expire_time)
            ->withRoleArn($role_ran)
            ->request();
        // 获取STS请求结果中的凭证信息。
        $tokenData = $result->get('Credentials');
        // 构建返回的JSON数据。
        $tempAccessKeyId = $tokenData['AccessKeyId'];
        $tempAccessKeySecret = $tokenData['AccessKeySecret'];
        $securityToken = $tokenData['SecurityToken'];

        $now = time();
        $dtObj = gmdate('Ymd\THis\Z', $now);
        $dtObj1 = gmdate('Ymd', $now);
        $dtObjPlus3h = gmdate('Y-m-d\TH:i:s.u\Z', strtotime('+3 hours', $now));

        // 构建Policy
        $policy = [
            "expiration" => $dtObjPlus3h,
            "conditions" => [
                ["x-oss-signature-version" => "OSS4-HMAC-SHA256"],
                ["x-oss-credential" => "{$tempAccessKeyId}/{$dtObj1}/cn-hangzhou/oss/aliyun_v4_request"],
                ["x-oss-security-token" => $securityToken],
                ["x-oss-date" => $dtObj],
            ]
        ];

        $policyStr = json_encode($policy);

        // 构造待签名字符串
        $stringToSign = base64_encode($policyStr);

        // 计算SigningKey
        $dateKey =$this->hmacsha256(('aliyun_v4' . $tempAccessKeySecret), $dtObj1);
        $dateRegionKey =$this->hmacsha256($dateKey, 'cn-hangzhou');
        $dateRegionServiceKey =$this->hmacsha256($dateRegionKey, 'oss');
        $signingKey =$this->hmacsha256($dateRegionServiceKey, 'aliyun_v4_request');

        // 计算Signature
        $result =$this->hmacsha256($signingKey, $stringToSign);
        $signature = bin2hex($result);

        // 返回签名数据
        $responseData = [
            'policy' => $stringToSign,
            'x_oss_signature_version' => "OSS4-HMAC-SHA256",
            'x_oss_credential' => "{$tempAccessKeyId}/{$dtObj1}/cn-hangzhou/oss/aliyun_v4_request",
            'x_oss_date' => $dtObj,
            'signature' => $signature,
            'host' => $host,
            'dir' => $upload_dir,
            'security_token' => $securityToken
        ];
        return $responseData;
    }
    public function hmacsha256($key, $data)
    {
        return hash_hmac('sha256', $data, $key, true);
    }
}
