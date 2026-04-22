<?php

namespace app\command;

use app\modules\myclass\CreateDatabase;
use app\modules\myclass\TwilioSms;
use app\process\AppointmentTask;
use app\process\SmsBillTask;
use app\process\AutoTopupPatrol;
use app\process\BirthdayTask;
use app\queue\redis\wallet\Balance;
use support\Redis;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Fukuball\Jieba\Jieba;
use Fukuball\Jieba\Finalseg;
use Webman\RedisQueue\Redis as RedisQueueRedis;

#[AsCommand('Test', 'Test')]
class Test extends Command
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'Name description');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        echo "开始执行：" . getMillisecondTime() . "\n";
        RedisQueueRedis::send('email-send-email', ['to' => 'admin@admin.com', 'subject' => 'Test', 'body' => 'Test']);
        return self::SUCCESS;
        // $path = '/uploads/8/QuickPays.csv';
        // echo $this->extractPathAfterPublic($path);   // 输出: public/uploads/8/QuickPays.csv
        // return 0;
        // $baseUrl    = 'https://fts.cardconnect.com/cardconnect/rest';  // CardPointe 网关基础URL
        $prizes = [
            ['value' => '奖品1', 'rate' => 1],
            ['value' => '奖品2', 'rate' => 2],
            ['value' => '奖品3', 'rate' => 3],
            ['value' => '奖品4', 'rate' => 4],
            ['value' => '奖品5', 'rate' => 5],
            ['value' => '奖品6', 'rate' => 6],
            ['value' => '奖品7', 'rate' => 7],
            ['value' => '奖品8', 'rate' => 8],
            ['value' => '奖品9', 'rate' => 9],
            ['value' => '奖品10', 'rate' => 10],
            ['value' => '谢谢参与', 'rate' => 90],
        ];

        $result = $this->lottery($prizes);
        print_r($result);
        die;
        // $segments = $this->buildWheelSegments($prizes);
        // print_r($segments);

        // return 0;
        $TwilioSms = new TwilioSms();
        $res =   $TwilioSms->sendMms('9176918038', 'helloe', ['/uploads/168145.jpg'], ['from' => '+15407246516']);

        var_dump($res);
        die;
        //ALLINONE:Z!@#EyU5LCbOJcq4V0xtZuTH
        // (new SmsBillTask())->startFn();
        // (new AutoTopupPatrol())->startFn();
        // (new AppointmentTask())->startFn();
        // (new BirthdayTask())->startFn();
        // $years = date('Y');
        // $months = date('m');

        // Redis::flushdb();//清除所有
        // (new CreateDatabase())->createLegal();
        // $balance_key = 'b:1';
        // $user_balance =   Redis::hGetAll($balance_key) ?? feature('user.wallet.getBalance', 1);
        // print_r($user_balance);
        //   $re =   feature('myclass.SensitiveFilter.detect','草泥马 大傻逼 哈哈哈');
        //   print_r($re);
        $phones = [
            '+1 888-123-4567', // true
            '1(888)123-4567',  // true
            '8881234567',      // true
            '+86 13800000000', // false
            '+44 7900 123456', // false
        ];

        foreach ($phones as $p) {
            var_dump($p, isUsMobile($p));
        }
        return self::SUCCESS;
    }

    public function extractPathAfterPublic($fullPath)
    {
        $pos = strpos($fullPath, 'public');
        if ($pos === false) {
            return $fullPath;
        }

        // 去掉 'public' 之前的所有内容，保留之后的完整路径
        return substr($fullPath, $pos + 6); // 6 = strlen('public')
    }

    /**
     * 计算大转盘上每个奖品的展示比例（角度 & 百分比）
     *
     * @param array $prizes 每项至少包含：id, name, rate(权重)
     * @return array 增加了 start_angle, end_angle, percent 字段
     */
    public   function buildWheelSegments(array $prizes): array
    {
        // 1. 计算总权重
        $totalWeight = 0;
        foreach ($prizes as $prize) {
            $w = $prize['rate'] ?? 0;
            if ($w > 0) {
                $totalWeight += $w;
            }
        }

        if ($totalWeight <= 0) {
            throw new \Exception('概率配置错误：总权重必须大于 0');
        }

        // 2. 依次计算每个奖品在轮盘上的角度区间
        $segments = [];
        $currentAngle = 0.0;

        foreach ($prizes as $prize) {
            $w = $prize['rate'] ?? 0;
            if ($w <= 0) {
                // 权重为 0 的，直接跳过（不在盘面画出来）
                continue;
            }

            // 该奖品所占整个圆的比例
            $ratio   = $w / $totalWeight;          // 0~1
            $angle   = 360 * $ratio;              // 对应角度
            $percent = round($ratio * 100, 2);    // 对应百分比（用于显示）

            $start = $currentAngle;
            $end   = $currentAngle + $angle;

            $segments[] = [
                'id'           => $prize['id'],
                'name'         => $prize['name'],
                'rate'  => $prize['rate'], // 原始权重
                'percent'      => $percent,              // 展示用百分比
                'start_angle'  => $start,                // 起始角度（顺时针）
                'end_angle'    => $end,                  // 结束角度
            ];

            // 更新起点，给下一个奖品用
            $currentAngle = $end;
        }

        return $segments;
    }
    private function rateToPoints($rate): int
    {
        $s = trim((string)$rate);

        // 允许：整数 / 1~3位小数；不允许负数/空/科学计数法/超过3位
        if (!preg_match('/^\d+(?:\.\d{1,3})?$/', $s)) {
            tryFun('rate_not_error');
        }

        [$intPart, $fracPart] = array_pad(explode('.', $s, 2), 2, '');
        $fracPart = str_pad($fracPart, 3, '0', STR_PAD_RIGHT); // 补足3位

        $points = ((int)$intPart) * 1000 + (int)$fracPart;

        // 单个 rate 不允许超过 100.000（可选，但建议加）
        if ($points > 100000) {
            tryFun('rate_not_error');
        }

        return $points;
    }
    private function lottery(array $prizes): array
    {
        // 1) 预处理：计算每项权重点位，并求和
        $items = [];
        $sumPoints = 0;

        foreach ($prizes as $key => $prize) {
            $points = $this->rateToPoints($prize['rate'] ?? null); // 允许最多3位小数

            // 允许 0（表示永不中），但不参与累积区间
            if ($points > 0) {
                $items[] = [
                    'key' => $key,
                    'prize' => $prize,
                    'points' => $points,
                ];
                $sumPoints += $points;
            }
        }

        // 2) 校验：总权重必须 > 0
        if ($sumPoints <= 0) {
            throw new \Exception(trans('rate_not_error'));
        }

        // 3) 按权重抽取：随机落点在 1..sumPoints
        $rand = mt_rand(1, $sumPoints);
        // 若你需要更强随机（防刷奖），建议用：$rand = random_int(1, $sumPoints);

        $cumulative = 0;
        foreach ($items as $item) {
            $cumulative += $item['points'];
            if ($rand <= $cumulative) {
                $result = $item['prize'];
                $result['key'] = $item['key'];
                unset($result['rate']);
                return $result;
            }
        }

        // 理论上不会到这里
        return [
            'value' => '未中奖',
            'key' => -1,
        ];
    }
}
