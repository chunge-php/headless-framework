const crypto = require('crypto');
const axios  = require('axios'); // npm install axios

// ======== 请填写你的 key ==========
const accessKey = '747FE502E08AFE3A2DDA2BF0956FD70B';
const secretKey = '8507770E176498652C9DA73041DA846BB3A203E4EC73009E6F58754CE17EBD20';

// ========== AES 加密（双重 Base64，完全模拟 PHP） ==========
function aesEncrypt(jsonData, secretKey) {
    const key = secretKey.substring(0, 16);
    const iv  = secretKey.substring(16, 32);

    const cipher = crypto.createCipheriv('aes-128-cbc', key, iv);
    let encrypted = cipher.update(jsonData, 'utf8', 'base64');
    encrypted += cipher.final('base64');

    // PHP 中 openssl_encrypt 已经返回 base64 了，然后又 base64_encode 了一次
    // 所以这里要再套一层
    return Buffer.from(encrypted).toString('base64');
}

// ========== HMAC-SHA256 ==========
function calcSign(params, secretKey) {
    const msg = params.access_key + params.timestamp + params.nonce + params.data;
    return crypto
        .createHmac('sha256', secretKey)
        .update(msg)
        .digest('hex')
        .toUpperCase();
}

// ========== 生成 nonce（16位hex） ==========
function randomHex(size = 16) {
    return crypto.randomBytes(size / 2).toString('hex');
}

// ========== POST 请求 ==========
async function httpPost(url, params) {
    try {
        const res = await axios.post(url, params, {
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        });
        return res.data;
    } catch (err) {
        return err.response ? err.response.data : err.message;
    }
}

// ========== 主流程 ==========
async function main() {
    // 业务数据（等价 PHP 的 $business）
    const business = {
        account_number: ["1900292929"],
        lessee_name: "测试商户1",
        lessee_code: "SH123",
        body: "验证码340399 请不要告诉其他人",
        send_type: 0,
        mms_url: "",
        subject: ""
    };

    const jsonData = JSON.stringify(business);

    const params = {
        access_key: accessKey,
        timestamp : Date.now().toString(),
        nonce     : randomHex(16),
        data      : aesEncrypt(jsonData, secretKey),
    };

    params.sign = calcSign(params, secretKey);

    console.log('请求参数：', params);

    const url = "http://127.0.0.1:5500/api/clients/sendSms";
    const result = await httpPost(url, params);

    console.log('响应结果：', result);
}

main();
