<?php

namespace app\modules\myclass;

use Mailgun\Mailgun;
use Exception;

trait DualMailgun
{
    public static function __callStatic($name, $arguments)
    {
        return (new self())->$name(...$arguments);
    }
}

class MailgunService
{
    use DualMailgun;
    protected $mailgun;
    protected $domain;
    protected $from;

    public function __construct()
    {
        $apiKey = config('mailgun.secret');
        $endpoint = config('mailgun.endpoint', 'api.mailgun.net');
        $this->domain = config('mailgun.domain');
        $this->from = config('mailgun.from');
        $this->mailgun = Mailgun::create($apiKey, $endpoint);
    }

    /**
     * 发送带有可选 .ics 附件的邮件
     *
     * @param string $to 收件人
     * @param string $subject 主题
     * @param string|null $html HTML 格式内容（可选）
     * @param bool $includeICS 是否包含 .ics 附件
     * @param int|null $startTime 开始时间的时间戳
     * @param int|null $endTime 结束时间的时间戳
     * @param string|null $location 预约地点
     * @param string|null $description 描述
     * @param string $organizerName 组织者名称
     * @param string $organizerEmail 组织者邮箱
     * @return array 返回包含消息和状态代码的数据
     */
    public function sendEmail($to, $subject, $html = null, $includeICS = false, $startTime = null, $endTime = null, $location = null, $description = null, $organizerName = null, $organizerEmail = null)
    {
        $params = [
            'from'    => $this->from,
            'to'      => $to,
            'subject' => $subject,
            'html'    => $html,
        ];

        if ($includeICS) {
            $icsContent = $this->generateICSContent($subject, $startTime, $endTime, $location, $description, $organizerName, $organizerEmail);
            $icsFilePath = tempnam(sys_get_temp_dir(), 'event') . '.ics';
            file_put_contents($icsFilePath, $icsContent);
            $params['attachment'] = [
                ['filePath' => $icsFilePath, 'filename' => 'event.ics']
            ];
        }

        $data = [
            'success' => false,
            'error' => 'Error sending email',
            'code' => 0,
        ];

        try {
            if (config('app.debugs')) {
                $data['error'] = 'ok';
                $data['code'] = 200;
                $data['success'] = true;
            } else {
                $response = $this->mailgun->messages()->send($this->domain, $params);
                if ($response) {
                    $data['error'] = $response->getMessage();
                    $data['code'] = 200;
                    $data['success'] = true;
                }
            }
            return $data;
        } catch (Exception $e) {
            debugMessage($e->getMessage(), '邮件发送失败');
            $data['error'] = $e->getMessage();
            $data['code'] = $e->getCode();
            return $data;
        } finally {
            if (isset($icsFilePath) && is_file($icsFilePath)) {
                @unlink($icsFilePath);
            }
        }
    }

    /**
     * 生成 .ics 文件内容
     *
     * @param string $subject 事件主题
     * @param int $startTime 开始时间的时间戳
     * @param int $endTime 结束时间的时间戳
     * @param string $location 预约地点
     * @param string $description 描述
     * @param string $organizerName 组织者名称
     * @param string $organizerEmail 组织者邮箱
     * @return string 返回生成的 .ics 文件内容
     */
    protected function generateICSContent($subject, $startTime, $endTime, $location = 'Online', $description = 'Event Invitation', $organizerName = null, $organizerEmail = null)
    {
        $formattedStartTime = gmdate('Ymd\THis\Z', $startTime);
        $formattedEndTime = gmdate('Ymd\THis\Z', $endTime);

        $icsContent = "BEGIN:VCALENDAR\r\n";
        $icsContent .= "VERSION:2.0\r\n";
        $icsContent .= "PRODID:-//YourCompany//NONSGML Event//EN\r\n";
        $icsContent .= "BEGIN:VEVENT\r\n";
        $icsContent .= "UID:" . uniqid() . "@yourdomain.com\r\n";
        $icsContent .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
        $icsContent .= "DTSTART:" . $formattedStartTime . "\r\n";
        $icsContent .= "DTEND:" . $formattedEndTime . "\r\n";
        $icsContent .= "SUMMARY:" . $this->escapeString($subject) . "\r\n";
        $icsContent .= "DESCRIPTION:" . $this->escapeString($description) . "\r\n";
        $icsContent .= "LOCATION:" . $this->escapeString($location) . "\r\n";

        // 添加 ORGANIZER 字段
        if ($organizerName && $organizerEmail) {
            $icsContent .= "ORGANIZER;CN=" . $this->escapeString($organizerName) . ":mailto:" . $this->escapeString($organizerEmail) . "\r\n";
        }

        $icsContent .= "END:VEVENT\r\n";
        $icsContent .= "END:VCALENDAR\r\n";

        return $icsContent;
    }

    /**
     * 处理 .ics 内容中的特殊字符
     *
     * @param string $string 输入字符串
     * @return string 转义后的字符串
     */
    protected function escapeString($string)
    {
        return preg_replace('/([\,;])/', '\\\$1', $string);
    }
    /**
     * 生成注册页面的 HTML 内容
     * @param mixed $code
     * @param mixed $account_number
     * @return array
     */
    public function signUpHtml($code, $account_number)
    {
        $MigrationPathContent = file_get_contents(base_path('resource/stubs/sign-up.html'));
        $message = str_replace('{{code}}', $code, $MigrationPathContent);
        $logo_bas64 = config('app.logo_bas64');
        $message = str_replace('{{url}}', $logo_bas64, $message);
        $data_en = json_encode([
            'code' => $code,
            'account_number' => $account_number,
            'type' => state_zero
        ], JSON_UNESCAPED_UNICODE, JSON_UNESCAPED_SLASHES);
        $aesen =  AESEnCode($data_en);
        $verifyLink = config('app.domain_name_url') . '/login/verify-link?encrypted=' . urlencode($aesen['encrypted']) . '&iv=' . urlencode($aesen['iv']);
        $message = str_replace('{{verifyLink}}', $verifyLink, $message);
        $email_data = [
            'account_number' => $account_number,
            'subject' => 'Complete Your Sign-Up',
            'message' => $message,
        ];
        return $email_data;
    }
    /**
     * 找回密码
     * @return array
     */
    public function retrievePasswordHtml($password, $account_number,$name='')
    {
        $MigrationPathContent = file_get_contents(base_path('resource/stubs/retrieve_password.html'));
        $message = str_replace('{{TEMP_PASSWORD}}', $password, $MigrationPathContent);
        $message = str_replace('{{FirstName}}', $name, $message);
        $appname ='Quick Pays';
        $message = str_replace('{{AppName}}', $appname, $message);
        $email_data = [
            'account_number' => $account_number,
            'subject' => 'Your Temporary Password – Account Recovery',
            'message' => $message,
        ];
        return $email_data;
    }
    public function bindHtml($code, $account_number)
    {
        $MigrationPathContent = file_get_contents(base_path('resource/stubs/bind_email.html'));
        $message = str_replace('{{code}}', $code, $MigrationPathContent);
        $email_data = [
            'account_number' => $account_number,
            'subject' => 'Your Binding Verification Code',
            'message' => $message,
        ];
        return $email_data;
    }



    /* * 自动充值邮件内容
     * @param mixed $balance
     * @param mixed $recharge_price
     * @param mixed $later_number
     * @param mixed $when_price
     * @param mixed $name
     * @return array{message: array|string, subject: string}
     */
    public function autoRechargeHtml($balance, $recharge_price, $later_number, $when_price, $name)
    {
        $MigrationPathContent = file_get_contents(base_path('resource/stubs/auto-recharge.html'));
        $message = str_replace('{{url}}', config('app.logo_bas64'), $MigrationPathContent);
        $message = str_replace('{{balance}}', $balance, $message);
        $message = str_replace('{{recharge_price}}', $recharge_price, $message);
        $message = str_replace('{{later_number}}', $later_number, $message);
        $message = str_replace('{{when_price}}', $when_price, $message);
        $message = str_replace('{{Firstname}}', $name, $message);
        return [
            'subject' => 'BeautyBooking Auto Recharge',
            'message' => $message,
        ];
    }
    /**
     * 自动充值邮件内容
     * @param mixed $balance
     * @param mixed $price
     * @param mixed $recharge_price
     * @param mixed $name
     * @param mixed $err
     * @return array{message: array|string, subject: string}
     */
    public function autoRechargeHtmlError($balance, $recharge_price, $name, $err = '')
    {
        $MigrationPathContent = file_get_contents(base_path('resource/stubs/auto-recharge-error.html'));
        $message = str_replace('{{url}}', config('app.logo_bas64'), $MigrationPathContent);
        $message = str_replace('{{balance}}', $balance, $message);
        $message = str_replace('{{recharge_price}}', $recharge_price, $message);
        $message = str_replace('{{Firstname}}', $name, $message);
        $message = str_replace('{{message}}', $err, $message);
        return [
            'subject' => 'BeautyBooking Auto Recharge',
            'message' => $message,
        ];
    }
    /**
     * 低余额提现
     * @param mixed $balance
     * @param mixed $name
     * @return array{message: array|string, subject: string}
     */
    public function lowBalanceHtml($balance, $name)
    {
        $MigrationPathContent = file_get_contents(base_path('resource/stubs/Low-balancereminder.html'));
        $message = str_replace('{{url}}', config('app.logo_bas64'), $MigrationPathContent);
        $message = str_replace('{{balance}}', $balance, $message);
        $message = str_replace('{{Firstname}}', $name, $message);
        return [
            'subject' => 'Low Balance Alert',
            'message' => $message,
        ];
    }
    /* * 无余额提醒
     * @param mixed $balance
     * @param mixed $name
     * @return array{message: array|string, subject: string}
     */
    public function notBalanceReminderHtml($balance, $name)
    {
        $MigrationPathContent = file_get_contents(base_path('resource/stubs/notBalance.html'));
        $message = str_replace('{{url}}', config('app.logo_bas64'), $MigrationPathContent);
        $message = str_replace('{{balance}}', $balance, $message);
        $message = str_replace('{{Firstname}}', $name, $message);
        return [
            'subject' => 'Your SMS Service Has Been Paused',
            'message' => $message,
        ];
    }
    /* * 自动月费邮件内容
     * @param mixed $price 月费
     * @param mixed $name 用户姓名
     * @param mixed $join_date 加入日期
     * @param mixed $date_number 未使用的天数
     * @param mixed $start_date 开始日期
     * @param mixed $end_date   结束日期
     * @param mixed $refund_price 退款金额
     * @param mixed $consume_money 实际消费金额
     * @return array{message: array|string, subject: string}
     */
    public function autoMonthHtml($price, $name, $join_date, $date_number, $start_date, $end_date, $plans_name = '', $refund_price = 0, $consume_money = 0)
    {
        $MigrationPathContent = file_get_contents(base_path('resource/stubs/autoMonthHtml.html'));
        $message = str_replace('{{url}}', config('app.logo_bas64'), $MigrationPathContent);
        $message = str_replace('{{price}}', $price, $message);
        $message = str_replace('{{Firstname}}', $name, $message);
        $message = str_replace('{{plans_name}}', $plans_name, $message);
        $message = str_replace('{{date}}', dayDate(), $message);
        $adjustment_box = '';
        if ($refund_price > 0) {
            $adjustment_box = <<<EOD
                 <div class="adjustment-box">
                <h3>Important Billing Adjustment</h3>
                <p>Since you joined on <strong>{$join_date}</strong>, we've calculated the prorated amount for the unused days:</p>
                <ul>
                    <li>Monthly Rate: \${$price}</li>
                    <li>Unused Days: <strong>{$date_number}sky</strong> days (from <strong>{$start_date}</strong> to <strong>{$end_date}</strong>)</li>
                    <li>Refund Amount: <strong>\${$refund_price}</strong> (The amount will be deducted from this bill.)</li>
                </ul>
                <p>This month's actual fee: \${$consume_money}=(\${$price} - \${$refund_price})</p>
                    </div>
                EOD;
        }
        $message = str_replace('{{adjustment_box}}', $adjustment_box, $message);
        return [
            'subject' => 'BeautyBooking Monthly Subscription',
            'message' => $message,
        ];
    }
}
