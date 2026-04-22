<?php

namespace app\modules\myclass;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use app\modules\user\card\model\Card;

class CardPointeGateway
{
    private string $baseUrl;
    private string $apiUsername;
    private string $apiPassword;
    private ?string $defaultMerchid;
    private  $card;
    private Client $client;

    public function __construct()
    {


        $this->card =  new Card();
        // 假设从配置中获取以下参数
        $baseUrl    = 'https://fts.cardconnect.com/cardconnect/rest';  // CardPointe 网关基础URL

        //ALLINONE:Z!@#EyU5LCbOJcq4V0xtZuTH
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiUsername = config('app.fiserv_user_name');
        $this->apiPassword = config('app.fiserv_pwd');
        $this->defaultMerchid = config('app.fiserv_merchid');
        $authString = base64_encode($this->apiUsername . ':' . $this->apiPassword);
        $timeStamp = round(microtime(true) * 1000); // 获取当前时间戳（毫秒级）
        $headers = [
            "Content-Type" => "application/json",
            "Timestamp" => $timeStamp,
            "Authorization" => 'Basic ' . $authString
        ];
        $this->client = new Client([
            'base_uri' => $this->baseUrl . '/',
            'headers' => $headers,
            'http_errors' => false,
            'verify' => false,
            'timeout' => 15.0
        ]);
    }
    /**
     * 
     * @param string $method
     * @param string $endpoint
     * @param array $body
     */
    private function sendRequest(string $method, string $endpoint, array $body = [])
    {
        try {
            $body['merchid'] = $this->defaultMerchid;
            $response = $this->client->request($method, $endpoint, [
                'json' => $body
            ]);
            return json_decode($response->getBody(), true);
        } catch (RequestException | ConnectException $e) {
            debugMessage("CardPointe API Error: " . $e->getMessage(),'信用卡支付');
            return null;
        }
    }

    /**
     * 处理支付授权（auth）
     * @param array $data 包含以下参数：
     * @return array|string|null
     */
    public function authPayment(array $data)
    {
        //发起支付
        return $this->sendRequest('POST', 'auth', $data);
    }
    /**
     * 创建支付Profile（profile）
     * @param array $data 持卡人信息，包括 account, expiry, name, postal 等
     */
    public function createProfile($uid, array $data)
    {
        $card_data = [
            'defaultacct' => 'Y',
            'profileupdate' => 'N',
            'cofpermission' => 'Y',
            'auoptout' => 'Y',
            'account' => $data['account'],
            'name' =>  $data['name'],
            'address' => $data['address'],
            'city' => $data['city'],
            'region' => $data['region'] ?? "NY",
            'country' => $data['country'] ?? "US",
            'postal' => $data['postal'],
            'expiry' => $data['expiry'],
            'phone' => $data['phone'] ?? '',
            'company' => $data['company'] ?? '',
            'default_state'=>state_zero

        ];
        $request_data =  $this->sendRequest('POST', 'profile', $card_data);
        if (empty($request_data)) tryFun(trans('bing_card_not') . '01', bing_card_not);
        if (isset($request_data['profileid']) && isset($request_data['token'])) {
            $request_data['uid']     = $uid;
            $request_data['status'] = state_one;
            $request_data['response_data']  = json_encode($request_data, 256);
            $where = $this->card->where('uid', $uid)->where('token', $request_data['token']);
            $total = $where->count();
            if ($total <= 1) {
                $request_data['default_state'] = state_one;
            }
            $user_card  = $where->first();
            $data['later_number'] =  substr($data['account'], -4);
            if (!empty($user_card)) {
                $user_cards_id = $user_card->id;
                $this->card->where('id', $user_cards_id)->update(filterFields(array_merge($request_data, $data), $this->card));
            } else {
                $user_cards_id =   $this->card->insertGetId(filterFields(array_merge($request_data, $data), $this->card));
            }
            return $user_cards_id ? $user_cards_id :  tryFun(trans('bing_card_not') . '02', bing_card_not);
        } else {
            if (isset($request_data['resptext'])) {
                tryFun('operation failure: ' . $request_data['resptext'], bing_card_not);
            }
        }
        tryFun(trans('bing_card_not') . '03', bing_card_not);
    }

    /**
     * 更新支付Profile（profile）
     */
    public function updateProfile($uid, array $data)
    {
        $user_card  = $this->card->where('uid', $uid)->where('id', $data['id'])->first()?->toArray();
        if (empty($user_card)) {
            tryFun('user_info_not', user_info_not);
        }
        $card_data = [
            'defaultacct' => 'Y',
            'profileupdate' => 'N',
            'cofpermission' => 'Y',
            'auoptout' => 'Y',
            'profile' =>  $user_card['acctid'],
            'account' =>  $user_card['token'],
            'name' =>  $data['name'],
            'address' => $data['address'],
            'city' => $data['city'],
            'region' => $data['region'] ?? "NY",
            'country' => $data['country'] ?? "US",
            'postal' => $data['postal'],
            'expiry' => $data['expiry'],
            'phone' => $data['phone'] ?? '',
            'company' => $data['company'] ?? ''
        ];
        $request_data =  $this->sendRequest('PUT', 'profile', $card_data);
        if (empty($request_data)) tryFun(trans('bing_card_not') . '01', bing_card_not);
        if (isset($request_data['respcode']) && isset($request_data['acctid']) && ($request_data['respcode'] == '00' || $request_data['respcode'] == '09' || $request_data['respcode'] == '000' || $request_data['respcode'] == '0')) {
            $request_data['status'] = state_one;
            $request_data['response_data']  = json_encode($request_data, 256);
            $user_cards_id = $user_card['id'];
            $this->card->where('id', $user_cards_id)->update(filterFields(array_merge($request_data, $data), $this->card));
            return $user_cards_id ? $user_cards_id :  tryFun(trans('bing_card_not') . '02', bing_card_not);
        } else {
            if (isset($request_data['resptext'])) {
                tryFun('operation failure: ' . $request_data['resptext'], info_err);
            }
        }
        tryFun(trans('info_err') . '03', info_err);
    }




    /**
     * 获取支付Profile（profile）
     * @param string $profileid 资料ID
     * @param string $accountid 账户ID
     * @param string $merchid 商户ID
     */
    public function getProfile(string $profileid, string $accountid, string $merchid)
    {
        return $this->sendRequest('GET', "profile/$profileid/$accountid/$merchid");
    }
}
