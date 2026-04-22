<?php

namespace app\modules\sms\register\fns;

use app\modules\myclass\TwilioSms;
use app\modules\sms\register\model\CodeLog;
use Carbon\Carbon;

class SmsFn
{
    private $codeLog;
    private $twilioSms;
    public function __construct(CodeLog $codeLog, TwilioSms $twilioSms)
    {

        $this->codeLog = $codeLog;
        $this->twilioSms = $twilioSms;
    }
    public function send($account_number, $auto_type, $template_type, $password = '', $name = '')
    {
        $res = [
            'code' => '',
            'msg' => 'ok',
            'status' => ''
        ];
        if (!in_array($auto_type, [state_zero, state_one])) {
            $res['msg'] = 'auto_type_error';
            $res['status'] = auto_type_error;
            return $res;
        }
        if (empty($account_number)) tryFun('account_number_not_input', info_err);
        try {
            $total = $this->codeLog
                ->where('account_number', $account_number)
                ->where('auto_type', $auto_type)
                ->count();
            if ($total > 4) {
                $res['msg'] = 'code_get_total';
                $res['status'] = code_get_total;
                return $res;
            }
            $code = generateNumericCode();
            $code_log_id = $this->codeLog->insertGetId(['account_number' => $account_number, 'auto_type' => $auto_type, 'code' => $code, 'created_at' => dayDateTime()]);
            if ($auto_type == state_one) {
                if ($template_type == state_one) {
                    $MigrationPathContent = file_get_contents(base_path('resource/stubs/bind_sms.stub'));
                    $message = str_replace('{{code}}', $code, $MigrationPathContent);
                } else {
                    $MigrationPathContent = file_get_contents(base_path('resource/stubs/sign-up-code.stub'));
                    $message = str_replace('{{code}}', $code, $MigrationPathContent);
                }

                $data = $this->twilioSms->sendSms($account_number, $message);
            } elseif ($auto_type == state_zero) {
                if ($template_type == state_one) {
                    $subject_html  =  feature('myclass.MailgunService.bindHtml', $code, $account_number);
                } elseif ($template_type == state_two) {
                    $subject_html  =  feature('myclass.MailgunService.retrievePasswordHtml', $password, $account_number, $name);
                } else {
                    $subject_html  =  feature('myclass.MailgunService.signUpHtml', $code, $account_number);
                }
                $data =   feature('myclass.MailgunService.sendEmail', $subject_html['account_number'], $subject_html['subject'], $subject_html['message']);
            }
            $this->codeLog->where('id', $code_log_id)->update(['msg' => $data['error'] ?? null]);
            $res['code'] = $code;
            $res['msg'] = $data['error'] ?? null;
            return $res;
        } catch (\Exception $e) {
            debugMessage($e->getMessage(), '发送验证码失败');
            $res['msg'] = 'code_send_err';
            $res['status'] = code_send_err;
            return $res;
        }
    }

    public function verifyCode($account_number,  $code)
    {
        $auto_type =  detectEmailOrPhone($account_number);
        $total = $this->codeLog
            ->where('account_number', $account_number)
            ->where('auto_type', $auto_type)
            ->where('code', $code)
            ->where('created_at', '>=', date('Y-m-d H:i:s', time() - 5 * 60)) // 查找5分钟以内的数据
            ->exists();
        if ($total) {
            $this->codeLog->where('account_number', $account_number)
                ->where('auto_type', $auto_type)
                ->delete();
            return true;
        } else {
            $this->codeLog->where('account_number', $account_number)
                ->where('auto_type', $auto_type)
                ->delete();
        }
        return false;
    }
}
