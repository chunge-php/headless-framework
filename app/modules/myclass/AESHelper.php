<?php

namespace app\modules\myclass;


class AESHelper
{
    private $key; // 密钥
    private $iv;  // 初始化向量 (IV)

    public function __construct($key = null, $iv = null)
    {
        // 初始化密钥和 IV（可以随机生成或通过参数传递）
        $this->key = $key ?? openssl_random_pseudo_bytes(16); // 16 字节 (128 位密钥)
        $this->iv = $iv ?? openssl_random_pseudo_bytes(16);   // 16 字节 IV
    }

    /**
     * 获取 Base64 编码的密钥
     */
    public function getKey()
    {
        return base64_encode($this->key);
    }
    public function setKey()
    {
        $key = base64_encode(substr(hash('sha256', $this->iv, true), 0, 16));
        $this->key =   base64_decode($key);
        return $this;
    }
    public function setIv($iv)
    {
        $this->iv = base64_decode($iv);
        return $this;
    }

    /**
     * 获取 Base64 编码的 IV
     */
    public function getIV()
    {
        return base64_encode($this->iv);
    }

    /**
     * AES 加密
     * @param string $data 待加密的明文
     * @return string 加密后的密文（Base64 编码）
     */
    public function encrypt($data)
    {
        return base64_encode(openssl_encrypt(
            $data,
            'AES-128-CBC',
            $this->key,
            OPENSSL_RAW_DATA,
            $this->iv
        ));
    }

    /**
     * AES 解密
     * @param string $encryptedData 加密后的密文（Base64 编码）
     * @return string 解密后的明文
     */
    public function decrypt($encryptedData)
    {
        return openssl_decrypt(
            base64_decode($encryptedData),
            'AES-128-CBC',
            $this->key,
            OPENSSL_RAW_DATA,
            $this->iv
        );
    }
}
