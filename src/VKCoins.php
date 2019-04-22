<?php
/**
 * Created by PhpStorm.
 * User: Назым
 * Date: 22.04.2019
 * Time: 20:54
 */

namespace DigitalStar\vk_api;


require_once('config_library.php');

/**
 * Class VKCoins
 * @package DigitalStar\vk_api
 */
class VKCoins
{
    /**
     * @var string
     */
    protected $merchant_id = '';
    /**
     * @var array
     */
    private $request_ignore_error = REQUEST_IGNORE_ERROR;
    /**
     * @var string
     */
    private $merchant_key = '';

    /**
     * vk_api constructor.
     * @param $token
     * @param $merchant_id
     */
    public function __construct($token, $merchant_id)
    {
        $this->merchant_key = $token;
        $this->merchant_id = $merchant_id;
    }

    /**
     * @param $token
     * @param $merchant_id
     * @return VKCoins
     */
    public static function create($token, $merchant_id)
    {
        return new self($token, $merchant_id);
    }

    /**
     * @param $user_id
     * @param int $amount
     * @return array|bool
     * @throws VkApiException
     */
    public function sendTransfer($user_id, $amount)
    {
        $amount = ['amount' => $amount];
        $user_id = ['toId' => $user_id];
        return $this->request('send', $amount + $user_id);
    }

    /**
     * @param $method
     * @param array $params
     * @return bool|mixed
     * @throws VkApiException
     */
    public function request($method, $params = [])
    {
        $params['merchantId'] = $this->merchant_id;
        $params['key'] = $this->merchant_key;

        list($method, $params) = $this->editRequestParams($method, $params);
        $url = 'https://coin-without-bugs.vkforms.ru/merchant/' . $method . '/';
        while (True) {
            try {
                return $this->request_core($url, $params);
            } catch (VkApiException $e) {
                sleep(1);
                $exception = json_decode($e->getMessage(), true);
                if (in_array($exception['error']['error_code'], $this->request_ignore_error))
                    continue;
                else
                    throw new VkApiException($e->getMessage());
            }
        }
        return false;
    }

    /**
     * @param $method
     * @param $params
     * @return array
     */
    protected function editRequestParams($method, $params)
    {
        return [$method, $params];
    }

    /**
     * @param $url
     * @param array $params
     * @return mixed
     * @throws VkApiException
     */
    private function request_core($url, $params = [])
    {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE));
            $result = json_decode(curl_exec($ch), True);
            curl_close($ch);
        } else {
            $result = json_decode(file_get_contents($url, true, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => http_build_query($params)
                ]
            ])), true);
        }
        if (!isset($result) or isset($result['error']))
            throw new VkApiException(json_encode($result));
        if (isset($result['response']))
            return $result['response'];
        else
            return $result;
    }

    /**
     * @param array $user_ids
     * @return array|bool
     * @throws VkApiException
     */
    public function getBalance($user_ids = [])
    {
        $user_ids = ['userIds' => [empty($user_ids) ? $this->merchant_id : $user_ids]];
        return $this->request('score', $user_ids);
    }


    /**
     * @param string $name
     * @return array|bool
     * @throws VkApiException
     */
    public function setStoreName($name)
    {
        return $this->request('set', ['name' => $name]);
    }


    /**
     * @param string $url
     * @return array|bool
     * @throws VkApiException
     */
    public function setCallBack($url = null)
    {
        return $this->request('set', ['callback' => $url]);
    }


    /**
     * @return array|bool
     * @throws VkApiException
     */
    public function unsetCallBack()
    {
        return $this->request('set', ['callback' => null]);
    }


    /**
     * @return array|bool
     * @throws VkApiException
     */
    public function getCallBackLogs()
    {
        return $this->request('set', ['status' => 1]);
    }

    /**
     * @param int $sum
     * @param int $payload
     * @param bool $fixed_sum
     * @param bool $to_hex
     * @return string
     */
    public function getPaymentLink($sum, $payload = 0, $fixed_sum = true, $to_hex = true)
    {
        $payload = $payload ? $payload : rand(-2000000000, 2000000000);

        if ($to_hex) {
            $merchant_id = dechex($this->merchant_id);
            $sum = dechex($sum);
            $payload = dechex($payload);
            return 'vk.com/coin#m' . $merchant_id . '_' . $sum . '_' . $payload . ($fixed_sum ? '' : '_1');
        } else {
            $merchant_id = $this->merchant_id;
            return 'vk.com/coin#x' . $merchant_id . '_' . $sum . '_' . $payload . ($fixed_sum ? '' : '_1');
        }
    }

    /**
     * @param int $tx_type
     * @param int $last_tx
     * @return array|bool
     * @throws VkApiException
     */
    public function getTransactions($tx_type = 1, $last_tx = -228)
    {
        $tx_type = ['tx' => [$tx_type]];
        $last_tx = $last_tx != -228 ? ['lastTx' => $last_tx] : [];
        return $this->request('tx', $tx_type + $last_tx);
    }

    /**
     * @param object|array $data
     * @return bool
     */
    public function isKeysCorrespond($data)
    {
        if (is_object($data) && isset($data->id) && isset($data->from_id) && isset($data->amount) && isset($data->payload) && isset($data->key)) {
            $key = md5($data->id . ';' . $data->from_id . ';' . $data->amount . ';' . $data->payload . ';' . $this->merchant_key);
            return $data->key === $key;
        }
        return false;
    }
}