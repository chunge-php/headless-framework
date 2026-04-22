import javax.crypto.Cipher;
import javax.crypto.Mac;
import javax.crypto.spec.IvParameterSpec;
import javax.crypto.spec.SecretKeySpec;
import java.io.BufferedReader;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.security.SecureRandom;
import java.util.HashMap;
import java.util.Map;

public class ApiClientDemo {

    public static void main(String[] args) throws Exception {
        // TODO: 换成你自己的 accessKey / secretKey
        String accessKey = "747FE502E08AFE3A2DDA2BF0956FD70B";
        String secretKey = "8507770E176498652C9DA73041DA846BB3A203E4EC73009E6F58754CE17EBD20";

        // -------- 业务 JSON （等价于 PHP 的 $business）--------
        // 注意：Java 字符串里，双引号要用 \" 转义
        String businessJson = "{"
                + "\"account_number\":[\"1900292929\"],"
                + "\"lessee_name\":\"测试商户1\","
                + "\"lessee_code\":\"SH123\","
                + "\"body\":\"验证码340399 请不要告诉其他人\","
                + "\"send_type\":0,"
                + "\"mms_url\":\"\","
                + "\"subject\":\"\""
                + "}";

        // -------- 构建参数 --------
        Map<String, String> params = new HashMap<>();
        params.put("access_key", accessKey);
        params.put("timestamp", String.valueOf(System.currentTimeMillis()));
        params.put("nonce", randomHex(16));
        params.put("data", aesEncrypt(businessJson, secretKey));

        String sign = calcSign(params, secretKey);
        params.put("sign", sign);

        System.out.println("请求参数：");
        for (Map.Entry<String, String> e : params.entrySet()) {
            System.out.println(e.getKey() + " = " + e.getValue());
        }

        String url = "http://127.0.0.1:5500/api/clients/sendSms";
        String resp = httpPost(url, params);

        System.out.println("响应结果：");
        System.out.println(resp);
    }

    // ================== AES 加密（完全模拟 PHP：openssl_encrypt + 再 base64 一次）
    // ==================
    public static String aesEncrypt(String json, String secretKey) throws Exception {
        // 取前16字节作为 key，后16字节作为 iv
        String key = secretKey.substring(0, 16);
        String iv = secretKey.substring(16, 32);

        Cipher cipher = Cipher.getInstance("AES/CBC/PKCS5Padding");
        SecretKeySpec spec = new SecretKeySpec(key.getBytes(StandardCharsets.UTF_8), "AES");
        IvParameterSpec ivSpec = new IvParameterSpec(iv.getBytes(StandardCharsets.UTF_8));
        cipher.init(Cipher.ENCRYPT_MODE, spec, ivSpec);

        // 第一次：对原始二进制密文做 Base64（相当于 PHP 的 openssl_encrypt 返回值）
        byte[] encrypted = cipher.doFinal(json.getBytes(StandardCharsets.UTF_8));
        String base64Once = java.util.Base64.getEncoder().encodeToString(encrypted);

        // 第二次：对上面的 Base64 字符串再做一次 Base64（相当于 PHP 的 base64_encode($enc)）
        return java.util.Base64.getEncoder()
                .encodeToString(base64Once.getBytes(StandardCharsets.UTF_8));
    }

    // ================== HMAC-SHA256 签名（大写十六进制） ==================
    public static String calcSign(Map<String, String> params, String secretKey) throws Exception {
        String msg = params.get("access_key")
                + params.get("timestamp")
                + params.get("nonce")
                + params.get("data");

        Mac hmac = Mac.getInstance("HmacSHA256");
        SecretKeySpec keySpec = new SecretKeySpec(secretKey.getBytes(StandardCharsets.UTF_8), "HmacSHA256");
        hmac.init(keySpec);
        byte[] hash = hmac.doFinal(msg.getBytes(StandardCharsets.UTF_8));
        return bytesToHex(hash).toUpperCase();
    }

    // ================== 发送 POST 请求（application/x-www-form-urlencoded）
    // ==================
    public static String httpPost(String url, Map<String, String> params) throws Exception {
        URL u = java.net.URI.create(url).toURL();
        HttpURLConnection conn = (HttpURLConnection) u.openConnection();

        conn.setRequestMethod("POST");
        conn.setDoOutput(true);
        conn.setConnectTimeout(8000);
        conn.setReadTimeout(8000);
        conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded;charset=UTF-8");

        // 拼接 form 表单：key1=value1&key2=value2
        StringBuilder sb = new StringBuilder();
        for (Map.Entry<String, String> e : params.entrySet()) {
            if (sb.length() > 0) {
                sb.append("&");
            }
            sb.append(e.getKey())
                    .append("=")
                    .append(URLEncoder.encode(e.getValue(), "UTF-8"));
        }

        // 写入请求体
        try (OutputStream os = conn.getOutputStream()) {
            os.write(sb.toString().getBytes(StandardCharsets.UTF_8));
        }

        // 读取响应
        InputStream is;
        if (conn.getResponseCode() >= 400) {
            is = conn.getErrorStream();
        } else {
            is = conn.getInputStream();
        }

        StringBuilder resp = new StringBuilder();
        try (BufferedReader br = new BufferedReader(new InputStreamReader(is, StandardCharsets.UTF_8))) {
            String line;
            while ((line = br.readLine()) != null) {
                resp.append(line).append("\n");
            }
        }
        return resp.toString();
    }

    // ================== 工具函数 ==================
    private static String bytesToHex(byte[] bytes) {
        StringBuilder sb = new StringBuilder();
        for (byte b : bytes) {
            sb.append(String.format("%02x", b));
        }
        return sb.toString();
    }

    // 生成随机 nonce（16 字符 hex，等价 PHP 的 bin2hex(random_bytes(8))）
    private static String randomHex(int length) {
        byte[] buf = new byte[length / 2];
        new SecureRandom().nextBytes(buf);
        return bytesToHex(buf);
    }
}
