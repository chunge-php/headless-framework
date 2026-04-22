<?php

return [
    // 默认使用的存储驱动: local | aliyun | tencent | qiniu
    'default' => env('UPLOAD_DRIVER', 'local'),
    'chunk' => [
        'chunk_dir' => runtime_path() . '/upload_chunks', // 分片临时目录
        'chunk_expire' => 86400, // 分片过期时间(秒)
    ],
    // 本地存储相关配置
    'local' => [
        // 例如存储目录，可根据自己项目调整
        'upload_path' => public_path() . '/uploads',
        // 若需要URL前缀，用于拼接访问地址
        'url_prefix'   => '/uploads',
    ],

    // 阿里云OSS相关配置
    'aliyun' => [
        'access_key_id'     => env('ALIYUN_OSS_ACCESS_KEY_ID', ''),
        'access_key_secret' => env('ALIYUN_OSS_ACCESS_KEY_SECRET', ''),
        'bucket'            => env('ALIYUN_OSS_BUCKET', ''),
        'endpoint'          => env('ALIYUN_OSS_ENDPOINT', ''),  // 例如: oss-cn-hangzhou.aliyuncs.com
        'url_prefix'        => env('ALIYUN_OSS_URL_PREFIX', ''), // 例如: https://your-bucket.oss-cn-hangzhou.aliyuncs.com
        'role_arn'            => env('ALIYUN_OSS_STS_ROLE_ARN', ''),//角色arn
        // 断点续传相关
        'part_size'            => 8 * 1024 * 1024, // 8MiB
        'parallel_num'         => 3,
        'leave_parts_on_error' => true,            // 失败保留分片以便断点续传
        'checkpoint_dir' => runtime_path('upload_chunks'),
    ],

    // 腾讯云COS相关配置
    'tencent' => [
        'secret_id'   => env('TENCENT_COS_SECRET_ID', ''),
        'secret_key'  => env('TENCENT_COS_SECRET_KEY', ''),
        'region'      => env('TENCENT_COS_REGION', ''),  // 例如: ap-shanghai
        'bucket'      => env('TENCENT_COS_BUCKET', ''),
        'url_prefix'  => env('TENCENT_COS_URL_PREFIX', ''), // 例如: https://your-bucket.cos.ap-shanghai.myqcloud.com
    ],

    // 七牛云相关配置
    'qiniu' => [
        'access_key' => env('QINIU_ACCESS_KEY', ''),
        'secret_key' => env('QINIU_SECRET_KEY', ''),
        'bucket'     => env('QINIU_BUCKET', ''),
        'url_prefix' => env('QINIU_URL_PREFIX', ''), // 例如: http://xxx.bkt.clouddn.com
    ],

];
