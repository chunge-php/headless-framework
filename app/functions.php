<?php

use app\core\Foundation\FeatureRegistry;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use support\Db;
use Illuminate\Support\Arr;
use app\modules\myclass\AESHelper;
use Tinywan\ExceptionHandler\Exception\BadRequestHttpException;

/**
 * Here is your custom functions.
 */
/**
 * 统一成功返回
 * @param string | object | array | null  $data
 * @param mixed $msg
 * @param mixed $code
 * @return support\Response
 */
function success($data = [], $msg = 'ok', $code = 200, $token = null)
{
    $result = [
        'code' => $code,
        'msg' => $msg,
        'data' => $data,
        'token' => $token,
    ];
    return json($result);
}

/**
 * 统一错误返回
 * @param mixed $msg
 * @param mixed $code
 * @param mixed $data
 * @return support\Response
 */
function error($msg = 'error', $code = 1001, $data = [])
{
    $result = [
        'code' => $code,
        'msg' => trans($msg),
        'data' => $data
    ];

    return json($result)
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, Origin, X-Requested-With');
}
/**
 * 功能点调用
 * @param string|array|callable $key
 * @param array $args
 */
function feature($key, ...$args)
{
    try {
        return   FeatureRegistry::call($key, ...$args);
    } catch (\Exception $e) {
        // debugMessage($e->getMessage(),$key);
        $exists = str_contains($e->getMessage(), $key);
        if ($exists) {
            tryFun('function_not_found', function_not_found);
        } else {
            tryFun($e->getMessage(), $e->getCode());
        }
    }
}
function toStudlyClass(string $input): string
{
    // 统一分隔符为 /
    $input = trim(strtr($input, '\\', '/'));
    if ($input === '') return '';

    // 先按路径拆分，再按 - 和 _ 拆分为词
    $segments = preg_split('#/+?#', $input, -1, PREG_SPLIT_NO_EMPTY);
    $words = [];

    foreach ($segments as $seg) {
        foreach (preg_split('/[-_]+/', $seg, -1, PREG_SPLIT_NO_EMPTY) as $tok) {
            $words[] = ucfirst(strtolower($tok)); // 词首大写
        }
    }

    return implode('', $words);
}
/**
 * 随机获取6位数
 * @param mixed $length
 * @return string
 */
function generateNumericCode($length = 6)
{
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= rand(0, 9); // 每次生成一个0-9的随机数字
    }
    return $code;
}
/**
 * 打印日志
 */
function debugMessage($data = [], $message = '调试')
{

    // 检查是否处于调试模式
    if (!config('app.debug')) {
        return;
    }

    // 创建日志通道
    $log = new Logger('debug');
    $log->pushHandler(new StreamHandler(runtime_path('logs/debug.log'), Logger::DEBUG));

    // 如果数据是数组或对象，将其转换为字符串

    // 记录日志
    $log->debug($message, ['data' => $data]);
}
//-----------------------------------------------数据库迁移-----------------------------------------
/**
 * 定义字段属性。
 * @param  string $type 字段类型
 * @param string $comment 字段描述
 * @param mixed $default 字段默认值
 * @param int|null $length 字段长度（对于字符串类型有效）
 * @param bool $isNull 字段是否可以为 NULL
 * @param int|null $scale 总位数 当如果是浮点类型的字段时候保留小数几位
 * @return array 字段配置数组
 */
function setTableKey($type, $comment = '', $default = null, $length = null,  $isNull = false, $scale = null)
{
    $options = [
        'comment' => $comment,
        'null' => $isNull
    ];
    if ($default !== null) {
        $options['default'] = $default;
    }
    if ($length !== null) {
        $options['precision'] = $length; // 总位数
        if ($scale !== null) {
            $options['scale'] = $scale; // 小数位数
        }
    }
    if ($type == 'decimal') {
        $options['precision'] = 10;
        $options['scale'] = 2;
        $options['signed'] = false;
    }
    return ['type' => $type, 'options' => $options];
}

/**
 * 根据提供的规格添加或修改表中的列。
 * @param \Phinx\Db\Table $table 数据表对象
 * @param array $columns 列的设置
 */
function setTableForm($table, $columns)
{
    foreach ($columns as $columnName => $settings) {
        if ($table->hasColumn($columnName)) {

            $table->changeColumn($columnName, $settings['type'], $settings['options'])->update();
        } else {
            $table->addColumn($columnName, $settings['type'], $settings['options'])->update();
        }
    }
}
//------------------------------验证相关zhong---------------------------------
/**
 * 仅区分：email / phone / unknown（不验证手机号是否合法）
 * @param string $input
 * @return int
 */
function detectEmailOrPhone(string $input): int
{
    $raw = trim($input);

    // 1) 邮箱：含 @ 且基础校验通过
    if (str_contains($raw, '@') && filter_var($raw, FILTER_VALIDATE_EMAIL)) {
        return state_zero;
    }

    // 2) 手机：仅由数字与常见符号组成（不做长度/国家码合法性校验）
    //   允许的字符：0-9 + - 空格 ( ) .
    if ($raw !== '' && preg_match('/^[\d\+\-\s().]+$/', $raw)) {
        return state_one;
    }
    // 3) 其他
    return state_two;
}

//------------------------------加密函数---------------------------------


/**
 * AES加密
 * @param string $data
 * @return array{encrypted: string, iv: string}
 */
function AESEnCode($data)
{
    $iv = base64_encode(openssl_random_pseudo_bytes(16));
    $AESHelperModel = new AESHelper();
    $AESHelperModel->setIv($iv);
    $AESHelperModel->setKey();
    $encrypted = $AESHelperModel->encrypt($data);
    return [
        'encrypted' => $encrypted,
        'iv' => $iv,
    ];
}
/**
 * AES解密
 * @param mixed $encryptedData
 * @param mixed $iv
 */
function AESDeCode($encryptedData, $iv)
{
    $encryptedData = str_replace(' ', '+', $encryptedData);
    $iv = str_replace(' ', '+', $iv);
    $aESHelper = new  AESHelper();
    if (empty($encryptedData) || empty($iv)) {
        tryFun(trans('missing_encryption_data'), missing_encryption_data);
    }
    $aESHelper->setIv($iv);
    $aESHelper->setKey(); // 建议明确 key 的来源
    $json_data = $aESHelper->decrypt($encryptedData);
    $data = json_decode($json_data, true);
    return $data;
}
function tryFun($message = 'error', $code = 1001)
{
    // $param['errorCode'] = (int)$code;
    // $message = trans($message);
    // throw new BadRequestHttpException($message, $param);
    $message = trans($message);
    throw new Exception($message, $code);
}
function dayDate()
{
    $times = date('Y-m-d', time());
    return $times;
}

/**
 * 只允许发送美国短信：
 * - 支持格式：+1 888-123-4567 / 1(888)1234567 / 8881234567
 * - 返回 true：可以发
 * - 返回 false：当国际/非法号码处理
 */
function isUsMobile(string $raw): bool
{
    $raw = trim($raw);
    if ($raw === '') {
        return false;
    }

    // 是否显式写了 +
    $hasPlus = str_starts_with($raw, '+');

    // 只保留数字
    $digits = preg_replace('/\D+/', '', $raw) ?? '';

    if ($digits === '') {
        return false;
    }

    // 如果有 +，但是国家码不是 1，当国际短信直接拒绝
    if ($hasPlus && !str_starts_with($digits, '1')) {
        return false;
    }

    // 处理长度：
    // 11 位且以 1 开头：视为 1 + 10 位（国家码 + 本地）
    if (strlen($digits) === 11 && $digits[0] === '1') {
        $digits = substr($digits, 1); // 留 10 位本地号
    } elseif (strlen($digits) === 10) {
        // 10 位：当作美国本地号（没写 +1 / 1）
        // 不处理，直接往下走
    } else {
        // 其他长度：直接当国际/非法
        return false;
    }

    // 再保险：必须是 10 位纯数字
    if (strlen($digits) !== 10 || !ctype_digit($digits)) {
        return false;
    }

    // NANP 规则里区号首位不能是 0 或 1（简单防一波假号）
    if ($digits[0] === '0' || $digits[0] === '1') {
        return false;
    }

    return true;
}
/**
 * 将任意手机号字符串清洗为【仅数字】。
 * - 去掉空格、括号、连字符、加号、分机标记等一切非数字字符
 * - 兼容全角数字（０１２３…）→ 半角(0-9)
 * - 可选：限制最大长度（例如按 E.164 最大 15 位）
 *
 * @param string|int|float|null $input
 * @param int $maxLen  最大长度；0 表示不限制。常用 15（E.164）
 * @return string 仅由 0-9 组成
 */
function phone_to_digits($input,  $maxLen = 15)
{
    if ($input === null || $input === '') return '';

    // 1) 先把全角数字转半角
    static $full2half = ['０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4', '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9'];
    $s = strtr((string)$input, $full2half);

    // 2) 只保留所有“数字类字符”（含 Unicode 数字），再转成 ASCII 0-9
    // \p{Nd} 是十进制数字
    $s = preg_replace('/[^\p{Nd}]/u', '', $s) ?: '';

    // 3) 可选：裁剪长度（保留右侧 N 位，常用于保留本机/尾号）
    if ($maxLen > 0 && strlen($s) > $maxLen) {
        $s = substr($s, -$maxLen);
    }
    return $s;
}
/**
 * 转为整数
 * @param string|int $num
 * @return int
 */
function toNum($num): int
{
    if ($num > 0) {
        return sprintf("%.2f", $num * 100);
    } else {
        return 0;
    }
}
//------------------------------短信消耗长度计算---------------------------------
/**
 * 四舍五入到两位小数（字符串实现，避免浮点误差）
 * @param string|int|float $num  只包含数字与可选小数点、负号
 * @return string  固定两位，例如 "123.45"、"-0.01"
 */
function price_round2(string|int|float $num): string
{
    $s = (string)$num;
    $neg = false;
    if ($s !== '' && $s[0] === '-') {
        $neg = true;
        $s = substr($s, 1);
    }

    // 拆分整数/小数
    if (strpos($s, '.') !== false) {
        [$int, $frac] = explode('.', $s, 2);
    } else {
        $int = $s;
        $frac = '';
    }
    $int  = $int === '' ? '0' : ltrim($int, '0');
    $int  = $int === '' ? '0' : $int;
    $frac = $frac . '000';           // 补足至少3位，便于看第3位做四舍五入
    $d2   = substr($frac, 0, 2);     // 目标两位
    $d3   = (int)$frac[2];           // 第三位决定是否进位

    // 四舍五入处理
    $n2 = (int)$d2 + ($d3 >= 5 ? 1 : 0);
    if ($n2 >= 100) {
        // 小数两位溢出，整数 +1，小数重置为 00
        $int = (string)((int)$int + 1);
        $d2  = '00';
    } else {
        $d2 = str_pad((string)$n2, 2, '0', STR_PAD_LEFT);
    }

    $res = $int . '.' . $d2;
    return $neg && $res !== '0.00' ? '-' . $res : $res;
}

/**
 * 截断到两位小数（不进位）
 * @param string|int|float $num
 * @return string
 */
function price_floor2(string|int|float $num): string
{
    $s = (string)$num;
    $neg = false;
    if ($s !== '' && $s[0] === '-') {
        $neg = true;
        $s = substr($s, 1);
    }

    if (strpos($s, '.') !== false) {
        [$int, $frac] = explode('.', $s, 2);
    } else {
        $int = $s;
        $frac = '';
    }
    $int  = $int === '' ? '0' : ltrim($int, '0');
    $int  = $int === '' ? '0' : $int;
    $d2   = str_pad(substr($frac, 0, 2), 2, '0');

    $res = $int . '.' . $d2;
    return $neg && $res !== '0.00' ? '-' . $res : $res;
}
/**
 * 获取替换后的真实内容以及产生的价格内容长度
 * @param string|int $send_type 类型0短信1邮件
 * @param mixed $content 需要替换的内容
 * @param mixed $price 单价
 * @return array{consume_number: mixed, content: mixed, encoding: mixed, strlength: int, total_money: float|int}
 */
function getContentPriceLength($send_type, $content, $price): array
{
    $result_data = calculateSmsSegments($content);
    $result_data['total_segments'] = $send_type == state_two ? 1 : $result_data['total_segments'];
    $total_money =  calculatePriceText($price, $result_data['total_segments']);
    // $strlength = mb_strlen($result, 'UTF-8');
    // 2. 计算段数（向上取整）
    // $segments = ceil($strlength / $single_number);
    return [
        'content' => $content,
        'total_money' => $total_money,
        'strlength' => (int)$result_data['total_chars'], //总字数
        'consume_number' => $result_data['total_segments'], //消费总条数
        'encoding' => $result_data['encoding'], //编码类型0:GSM-7,1:UCS-2
    ];
}
/**
 * 计算短信分段
 *
 * @param string $message 短信内容
 * @return array 返回短信分段，每个分段是一个字符串
 */
function calculateSmsSegments(string $message): array
{
    // 智能编码替换：将非GSM字符替换为等效GSM字符
    $replacements = [
        '“' => '"',
        '”' => '"',  // 替换弯引号为直引号
        '‘' => "'",
        '’' => "'",  // 替换弯单引号为直单引号
        '…' => '...',            // 替换省略号
        '–' => '-',
        '—' => '-',  // 替换短/长破折号为连字符
    ];
    $normalized = strtr($message, $replacements);

    // GSM-7字符集定义（基本集+扩展集）
    $gsmChars = '@£$¥èéùìòÇ' . "\n" . 'Øø' . "\r" . 'ÅåΔ_ΦΓΛΩΠΨΣΘΞ' . "\x1B" .
        'ÆæßÉ !"#¤%&\'()*+,-./0123456789:;<=>?¡' .
        'ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿' .
        'abcdefghijklmnopqrstuvwxyzäöñüà' .
        '|^€{}[]~\\';  // 扩展字符

    // 检测是否包含非GSM字符
    $isGsm7 = true;
    $len = mb_strlen($normalized, 'UTF-8');
    for ($i = 0; $i < $len; $i++) {
        $char = mb_substr($normalized, $i, 1, 'UTF-8');
        if (mb_strpos($gsmChars, $char, 0, 'UTF-8') === false) {
            $isGsm7 = false;
            break;
        }
    }

    // 计算分段
    $totalChars = mb_strlen($normalized, 'UTF-8');
    $segments = [];
    $segmentCount = 0;

    if ($isGsm7) {
        // GSM-7编码规则
        $segmentCount = $totalChars <= 160 ? 1 : ceil($totalChars / 153);
        $remaining = $totalChars;
        while ($remaining > 0) {
            $segments[] = $remaining > 153 ? 153 : $remaining;
            $remaining -= 153;
        }
    } else {
        // UCS-2编码规则
        $segmentCount = $totalChars <= 70 ? 1 : ceil($totalChars / 67);
        $remaining = $totalChars;
        while ($remaining > 0) {
            $segments[] = $remaining > 67 ? 67 : $remaining;
            $remaining -= 67;
        }
    }
    //'GSM-7' : 'UCS-2'
    return [
        'encoding' => $isGsm7 ? 0 : 1,
        'total_segments' => $segmentCount,
        'segments' => $segments,
        'total_chars' => $totalChars,
        'normalized' => $normalized
    ];
}
/**
 * 根据条数 计算价格
 * @param mixed $price
 * @param mixed $total_segments
 * @return float|int
 */
function calculatePriceText($price = 0.02, $total_segments = 1)
{
    // 3. 计算总价格
    $totalPrice = $total_segments * $price;
    return $totalPrice;
}
/**
 * 获取指定时间加N天后的所有日期
 */
function getAddDateTime($begin_time, $number = 1)
{
    $end_time  = date('Y-m-d', strtotime($begin_time) + (86400 * $number) - 86400);
    return getDateFromRange($begin_time, $end_time);
}
/**
 * 获取开始日期到结束日期内的所有日期
 *
 * @param [type] $startdate
 * @param [type] $enddate
 * @return array
 */
function getDateFromRange(string $startdate, string $enddate, $desc = 'asc'): array
{
    $stimestamp = strtotime($startdate);
    $etimestamp = strtotime($enddate);

    // 计算日期段内有多少天
    $days = ($etimestamp - $stimestamp) / 86400 + 1;

    // 保存每天日期
    $date = array();

    for ($i = 0; $i < $days; $i++) {
        $date[] = date('Y-m-d', $stimestamp + (86400 * $i));
    }
    if ($desc == 'desc') {
        rsort($date);
    } else {
        sort($date);
    }
    return $date;
}
/**
 * 获取当前时间的毫秒级表示
 *
 * @return string 返回格式化的毫秒级时间，例如 "2023-10-01 12:34:56.78900"
 */
function getMillisecondTime(): string
{
    // 获取当前时间的微秒数
    $microtime = microtime(true);

    // 提取毫秒部分
    $milliseconds = sprintf("%05d", ($microtime - floor($microtime)) * 100000);

    // 格式化日期和时间，并拼接毫秒
    $formatted_time = date("Y-m-d H:i:s", (int)$microtime) . '.' . $milliseconds;

    return $formatted_time;
}
/**
 * 终端颜色格式化函数
 * 
 * @param string $text 要着色的文本
 * @param string $color 颜色名称 (green, red, yellow, blue, magenta, cyan, white)
 */
function colorText(string $text, string $color = 'green')
{
    $colors = [
        'black'    => "\033[0;30m",
        'red'      => "\033[0;31m",
        'green'    => "\033[0;32m",
        'yellow'   => "\033[0;33m",
        'blue'     => "\033[0;34m",
        'magenta'  => "\033[0;35m",
        'cyan'     => "\033[0;36m",
        'white'    => "\033[0;37m",
        // 亮色
        'lred'     => "\033[1;31m",
        'lgreen'   => "\033[1;32m",
        'lyellow'  => "\033[1;33m",
        'lblue'    => "\033[1;34m",
        'lmagenta' => "\033[1;35m",
        'lcyan'    => "\033[1;36m",
        'lwhite'   => "\033[1;37m",
    ];

    $reset = "\033[0m";

    // 默认绿色
    $colorCode = $colors['green'];

    if (isset($colors[strtolower($color)])) {
        $colorCode = $colors[strtolower($color)];
    }

    echo  $colorCode . $text . $reset . PHP_EOL;
}
/**
 * 将多种输入（空值 / Unix秒/毫秒 / Excel序列号 / 各类字符串日期）统一转为指定格式的日期字符串
 *
 * @param mixed $input  原始输入
 * @param string $format 返回格式（默认 Y-m-d；可设为 'Y-m-d H:i:s' 等）
 * @param DateTimeZone|null $tz 目标时区（不传则使用系统默认时区）
 * @return string  按格式返回；无法解析时返回空字符串
 */
function toYmdOrEmpty($input, string $format = 'Y-m-d', ?DateTimeZone $tz = null): string
{
    // 统一的格式化闭包
    $fmt = function (DateTime $dt) use ($format, $tz): string {
        if ($tz instanceof DateTimeZone) {
            $dt->setTimezone($tz);
        } else {
            // 与系统默认时区保持一致
            $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
        }
        return $dt->format($format);
    };

    // 1) 空值直接返回空
    if ($input === null) return '';
    if (is_string($input) && trim($input) === '') return '';

    // 2) 处理数值：时间戳 / Excel 序列号
    if (is_int($input) || is_float($input) || (is_string($input) && is_numeric($input))) {
        $num = (float)$input;

        // 2.1 可能是毫秒级时间戳（> 10^11 视为毫秒）
        if ($num > 100000000000) {
            $num = (int)round($num / 1000);
        }

        // 2.2 秒级 Unix 时间戳（1971年后基本都 > 31536000；并限定 < 3000-01-01）
        if ($num >= 31536000 && $num < 32503680000) { // 1971-01-01 ~ 2999-12-31
            $dt = (new DateTime())->setTimestamp((int)$num);
            return $fmt($dt);
        }

        // 2.3 Excel 序列号（支持小数部分表示时间）
        // Windows 基准：'1899-12-30'（兼容 1900 闰年 bug）
        if ($num > 0) {
            $base = new DateTime('1899-12-30 00:00:00');
            $days = (int)floor($num);
            $fraction = $num - $days;
            if ($days > 0) {
                $base->add(new DateInterval('P' . $days . 'D'));
            }
            if ($fraction > 0) {
                // 小数天 -> 秒（避免浮点误差，四舍五入）
                $sec = (int)round($fraction * 86400);
                if ($sec !== 0) {
                    $base->add(new DateInterval('PT' . $sec . 'S'));
                }
            }
            return $fmt($base);
        }
    }

    // 3) 处理字符串：尽可能解析多种格式
    if (is_string($input)) {
        $s = trim($input);

        // 常见无分隔/中文日期：20251018、2025年10月18日
        if (preg_match('/^\d{8}$/', $s)) { // 20251018
            $y = substr($s, 0, 4);
            $m = substr($s, 4, 2);
            $d = substr($s, 6, 2);
            if (checkdate((int)$m, (int)$d, (int)$y)) {
                $dt = DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $y, $m, $d));
                if ($dt instanceof DateTime) return $fmt($dt);
            }
        }
        if (preg_match('/^\s*(\d{4})[年\/\-.](\d{1,2})[月\/\-.](\d{1,2})[日]?\s*$/u', $s, $m)) {
            if (checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
                $dt = DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]));
                if ($dt instanceof DateTime) return $fmt($dt);
            }
        }

        // 使用 DateTime::createFromFormat 尝试多种格式（含时间）
        $formats = [
            'Y-m-d',
            'Y/m/d',
            'Y.m.d',
            'm/d/Y',
            'm-d-Y',
            'd/m/Y',
            'd-m-Y',
            'M d, Y',
            'd M Y',
            'D, d M Y',

            // 含时间
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'm/d/Y H:i:s',
            'm/d/Y H:i',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'Y/m/d H:i:s',
            'Y.m.d H:i:s',

            DateTime::RFC3339,
            DateTime::RFC2822,
        ];
        foreach ($formats as $f) {
            $dt = DateTime::createFromFormat($f, $s);
            if ($dt instanceof DateTime) {
                $errors = DateTime::getLastErrors();
                if (empty($errors['warning_count']) && empty($errors['error_count'])) {
                    return $fmt($dt);
                }
            }
        }

        // 兜底：让 strtotime 尝试（如 "18 Oct 2025 14:30"）
        $ts = strtotime($s);
        if ($ts !== false) {
            $dt = (new DateTime())->setTimestamp($ts);
            return $fmt($dt);
        }
    }

    // 无法解析
    return '';
}

/**
 * 将任意货币字符串解析为标准小数（字符串），默认保留2位小数
 * 例："$1,299.50" -> "1299.50"；"€1.299,5" -> "1299.50"；"199.01元" -> "199.01"
 */
function money_to_decimal($input, $scale = 2)
{
    $s = trim((string)$input);
    if ($s === '') return str_pad('0', $scale > 0 ? $scale + 2 : 1, $scale > 0 ? '.0' : '0');

    // 负号：支持前导 - 或 () 记负
    $neg = false;
    if (preg_match('/^\((.*)\)$/u', $s, $m)) {
        $s = $m[1];
        $neg = true;
    }
    if (str_starts_with($s, '-')) {
        $s = ltrim($s, '-');
        $neg = true;
    }

    // 去除常见货币与文字
    $s = str_replace(['人民币', '元', '圆', 'CNY', 'USD', 'US$', 'CN¥', '￥', '¥', '$', '€', '£', ' '], '', $s);

    // 只保留数字与., 其余移除（包括全角符号等）
    $s = preg_replace('/[^\d\.,]/u', '', $s);

    // 判断小数点：最后出现的 . 或 , 视为小数分隔，其它分隔当做千分位移除
    $lastDot   = strrpos($s, '.');
    $lastComma = strrpos($s, ',');
    if ($lastDot !== false && $lastComma !== false) {
        if ($lastDot > $lastComma) {
            $dec = '.';
            $th = ',';
        } else {
            $dec = ',';
            $th = '.';
        }
    } elseif ($lastComma !== false && $lastDot === false) {
        $dec = ',';
        $th = '';
    } else {
        $dec = '.';
        $th = '';
    }
    if ($th)  $s = str_replace($th, '', $s);
    if ($dec !== '.') $s = str_replace($dec, '.', $s);

    // 保留“数字+首个点”的形式
    if (!preg_match('/^\d*(?:\.\d+)?$/', $s)) {
        // 清洗多余小数点
        $s = preg_replace('/\.(?=.*\.)/', '', $s); // 除最后一个点外，去掉所有点
        $s = preg_replace('/[^\d\.]/', '', $s);
    }
    if ($s === '' || $s === '.') $s = '0';

    // 定标到 $scale 位（不引入浮点运算）
    [$int, $frac] = array_pad(explode('.', $s, 2), 2, '');
    $int  = ltrim($int, '0');
    if ($int === '') $int = '0';
    $frac = substr($frac, 0, $scale);
    $frac = str_pad($frac, $scale, '0');

    $res = $int . ($scale > 0 ? ('.' . $frac) : '');
    if ($neg && $res !== str_pad('0', $scale > 0 ? $scale + 2 : 1, $scale > 0 ? '.0' : '0')) {
        $res = '-' . $res;
    }
    return $res;
}
/**
 * 密码加密
 * @param string|int $password
 * @return bool|string|null
 */
function getPasswordHash(string|int $password = '888888'): string
{
    $pwd = (string)$password;

    $hasArgon2id = defined('PASSWORD_ARGON2ID') && in_array(PASSWORD_ARGON2ID, password_algos(), true);

    if ($hasArgon2id) {
        $base = ['memory_cost' => 1 << 17, 'time_cost' => 3];

        try {
            // 先尝试 2 线程（如果实现不支持会抛 ValueError）
            return password_hash($pwd, PASSWORD_ARGON2ID, $base + ['threads' => 2]);
        } catch (\ValueError $e) {
            // 线程不被支持时回退到单线程
            if (str_contains($e->getMessage(), 'thread value other than 1')) {
                return password_hash($pwd, PASSWORD_ARGON2ID, $base + ['threads' => 1]);
            }
            // 其他错误继续抛出
            throw $e;
        }
    }

    // 没有 Argon2ID 的环境，回退到 Bcrypt
    return password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 11]);
}


/**
 * 验证密码是否匹配（简单版）
 */
function verifyPassword(string $plain, string $hash): bool
{
    return $hash !== '' && password_verify($plain, $hash);
}
// -----------------------------时间函数-----------------------------------
/**
 * 获取当前时间
 * @return string
 */
function dayDateTime()
{
    $times = date('Y-m-d H:i:s', time());
    return $times;
}

//------------------------------数据库相关---------------------------------
/**
 * 过滤数据库不存在的字段，并自动转换数据类型
 *
 * @param array $data 传入的原始数据
 * @param object $model Eloquent Model
 * @return array 过滤后的数据
 */
function filterFields(array $data, object $model, $my_data = [])
{
    $fillable = $model->getFillable();

    if (!empty($my_data)) {
        $fillable = $my_data;
    }
    $table = $model->getTable();

    // 获取数据库字段类型
    $columns = Db::select("SELECT COLUMN_NAME, DATA_TYPE FROM information_schema.columns WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE()", [$table]);
    $columnTypes = [];
    foreach ($columns as $column) {
        $columnTypes[$column->COLUMN_NAME] = $column->DATA_TYPE;
    }

    // 处理字段转换逻辑
    $convertDataTypes = function ($item) use ($fillable, $columnTypes) {
        // 过滤不存在的字段
        $filtered = Arr::only($item, $fillable);

        // 根据数据库字段类型转换数据格式
        foreach ($filtered as $key => $value) {
            if (isset($columnTypes[$key])) {
                switch ($columnTypes[$key]) {
                    case 'int':
                    case 'bigint':
                    case 'smallint':
                    case 'tinyint':
                        $filtered[$key] = (int) $value;
                        break;
                    case 'decimal':
                    case 'float':
                    case 'double':
                        $filtered[$key] = (float) $value;
                        break;
                    case 'boolean':
                        $filtered[$key] = (bool) $value;
                        break;
                    default:
                        $filtered[$key] = (string) $value;
                        break;
                }
            }
        }

        return $filtered;
    };

    // 处理二维数组（批量数据）
    if (!empty($data) && isset($data[0]) && is_array($data[0])) {
        return array_map($convertDataTypes, $data);
    }

    // 处理一维数组
    return $convertDataTypes($data);
}
