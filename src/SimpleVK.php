<?php

namespace DigitalStars\SimpleVK;

use Exception;

require_once('config_simplevk.php');

class SimpleVK {
    use ErrorHandler;
    protected $version;
    protected $data = [];
    protected $data_backup = [];
    protected $api_url = 'https://api.vk.com/method/';
    protected $token;
    private static $debug_mode = false;
    protected $auth = null;
    protected $request_ignore_error = REQUEST_IGNORE_ERROR;
    public static $proxy = PROXY;
    public static $proxy_types = ['socks4' => CURLPROXY_SOCKS4, 'socks5' => CURLPROXY_SOCKS5];
    private $is_test_len_str = true;

    public static function create($token, $version, $also_version = null) {
        return new self($token, $version, $also_version);
    }

    public function __construct($token, $version, $also_version = null) {
        $this->processAuth($token, $version, $also_version);
        $this->data = json_decode(file_get_contents('php://input'), 1);
        $this->data_backup = $this->data;
        if (isset($this->data['type']) && $this->data['type'] != 'confirmation') {
            if (self::$debug_mode) {
                $this->debugRun();
            } else {
                $this->sendOK();
            }
            if (isset($this->data['object']['message']) and $this->data['type'] == 'message_new') {
                $this->data['object'] = $this->data['object']['message'];
            }
        }
    }

    public function __call($method, $args = []) {
        $method = str_replace("_", ".", $method);
        $args = (empty($args)) ? $args : $args[0];
        return $this->request($method, $args);
    }

    public static function setProxy($proxy, $pass = false) {
        self::$proxy['ip'] = $proxy;
        self::$proxy['type'] = explode(':', $proxy)[0];
        if ($pass)
            self::$proxy['user_pwd'] = $pass;
    }

    public static function debug($flag = true) {
        self::$debug_mode = ($flag) ?: false;
    }

    public function setConfirm($str) {
        if (isset($this->data['type']) && $this->data['type'] == 'confirmation') {
            exit($str);
        }
        return $this;
    }

    public function setSecret($str) {
        if (isset($this->data['secret']) && $this->data['secret'] == $str) {
            return $this;
        }
        exit('security error');
    }

    public function initVars(&$id = null, &$message = null, &$payload = null, &$user_id = null, &$type = null) {
        $data = $this->data;
        $type = $data['type'] ?? null;
        $id = $data['object']['peer_id'] ?? null;
        $message = $data['object']['text'] ?? null;
        $payload = isset($data['object']['payload']) ? json_decode($data['object']['payload'], true) : null;
        $user_id = $data['object']['from_id'] ?? null;
        return $this->data_backup;
    }

    public function getAttachments($data = null) {
        $data = $data ?? $this->data;
        if (!isset($data['object']['attachments']))
            return false;
        $result = [];
        foreach ($data['object']['attachments'] as $attachment) {
            $type = $attachment['type'];
            $attachment = $attachment[$type];
            if (isset($attachment['sizes'])) {
                $preview = $attachment['sizes'];
                unset($attachment['sizes']);
            } else if (isset($attachment['preview']))
                $preview = $attachment['preview']['photo']['sizes'];
            else
                $preview = null;
            if ($preview) {
                $previews_result = [];
                foreach ($preview as $item) {
                    $previews_result[$item['type']] = $item;
                }
                if (empty($attachment['url']))
                    $attachment['url'] = end($previews_result)['url'];
                $attachment['preview'] = $previews_result;
            }
            $result[$type][] = $attachment;
        }
        return $result;
    }

    public function clientSupport(&$keyboard, &$inline, &$buttons) {
        $data = $this->data_backup['object']['client_info'];
        $keyboard = $data['keyboard'];
        $inline = $data['inline_keyboard'];
        $buttons = $data['button_actions'];
        return $data;
    }

    public function reply($message, $params = []) {
        return $this->request('messages.send', ['message' => $message, 'peer_id' => $this->data['object']['peer_id']] + $params);
    }

    public function sendMessage($id, $message, $params = []) {
        return $this->request('messages.send', ['message' => $message, 'peer_id' => $id] + $params);
    }

    public function forward($id, $id_messages, $params = []) {
        $forward_messages = (is_array($id_messages)) ? join(',', $id_messages) : $id_messages;
        return $this->request('messages.send', ['peer_id' => $id, 'forward_messages' => $forward_messages] + $params);
    }

    public function userInfo($user_url = '', $scope = []) {
        $scope = ["fields" => join(",", $scope)];
        if (isset($user_url)) {
            $user_url = preg_replace("!.*?/!", '', $user_url);
            $user_url = ($user_url == '') ? [] : ["user_ids" => $user_url];
        }
        try {
            return current($this->request('users.get', $user_url + $scope));
        } catch (Exception $e) {
            return false;
        }
    }

    public function sendKeyboard($id, $message, $keyboard = [], $inline = false, $one_time = False, $params = []) {
        $keyboard = $this->generateKeyboard($keyboard, $inline, $one_time);
        return $this->request('messages.send', ['message' => $message, 'peer_id' => $id, 'keyboard' => $keyboard] + $params);
    }

    public function buttonLocation($payload = null) {
        return ['location', $payload];
    }

    public function buttonOpenLink($link, $label = 'Открыть', $payload = null) {
        return ['open_link', $payload, $link, $label];
    }

    public function buttonPayToGroup($group_id, $amount, $description = null, $data = null, $payload = null) {
        return ['vkpay', $payload, 'pay-to-group', $group_id, $amount, $description, $data];
    }

    public function buttonPayToUser($user_id, $amount, $description = null, $payload = null) {
        return ['vkpay', $payload, 'pay-to-user', $user_id, $amount, $description];
    }

    public function buttonDonateToGroup($group_id, $payload = null) {
        return ['vkpay', $payload, 'transfer-to-group', $group_id];
    }

    public function buttonDonateToUser($user_id, $payload = null) {
        return ['vkpay', $payload, 'transfer-to-user', $user_id];
    }

    public function buttonApp($text, $app_id, $owner_id = null, $hash = null, $payload = null) {
        return ['open_app', $payload, $text, $app_id, $owner_id, $hash];
    }

    public function buttonText($text, $color = 'white', $payload = null) {
        return ['text', $payload, $text, self::$color_replacer[$color]];
    }

    static $color_replacer = [
        'blue' => 'primary',
        'white' => 'default',
        'red' => 'negative',
        'green' => 'positive'
    ];

    protected function placeholders($id, $message) {
        if($id >= 2e9) {
            $id = $this->data['object']['from_id'] ?? null;
        }
        if (strpos($message, '%') !== false) {
            $data = $this->userInfo($id);
            $f = $data['first_name'];
            $l = $data['last_name'];
            $tag = ['%fn%', '%ln%', '%full%', '%a_fn%', '%a_ln%', '%a_full%'];
            $replace = [$f, $l, "$f $l", "@id{$id}($f)", "@id{$id}($l)", "@id{$id}($f $l)"];
            return str_replace($tag, $replace, $message);
        } else
            return $message;
    }

    protected function lengthMessageProcessing($id, $str) {
        $bytes = 0;
        $tmp_str = '';
        $this->is_test_len_str = false;
        $anon = function ($a) use (&$tmp_str, &$bytes, $id) {
            $byte = strlen((@iconv('UTF-8', 'cp1251', $a[0]))
                ?: "$#".(unpack('V', iconv('UTF-8', 'UCS-4LE', $a[0]))[1]).";");
            $bytes += $byte;
            if ($bytes > 4096) {
                $this->sendMessage($id, $tmp_str);
                $bytes = $byte;
                $tmp_str = $a[0];
            } else
                $tmp_str .= $a[0];
            return "";
        };
        preg_replace_callback('/./u', $anon, $str);
        $this->is_test_len_str = true;
        return $tmp_str;
    }

    public function generateKeyboard($keyboard_raw = [], $inline = false, $one_time = False) {
        $keyboard = [];
        $i = 0;
        foreach ($keyboard_raw as $button_str) {
            $j = 0;
            foreach ($button_str as $button) {
                $keyboard[$i][$j]['action']['type'] = $button[0];
                if ($button[1] != null)
                    $keyboard[$i][$j]['action']['payload'] = json_encode($button[1], JSON_UNESCAPED_UNICODE);
                switch ($button[0]) {
                    case 'text': {
                        $keyboard[$i][$j]['color'] = $button[3];
                        $keyboard[$i][$j]['action']['label'] = $button[2];
                        break;
                    }
                    case 'vkpay': {
                        $keyboard[$i][$j]['action']['hash'] = "action={$button[2]}";
                        $keyboard[$i][$j]['action']['hash'] .= ($button[3] < 0) ? "&group_id=".$button[3]*-1 : "&user_id={$button[3]}";
                        $keyboard[$i][$j]['action']['hash'] .= (isset($button[4])) ? "&amount={$button[4]}" : '';
                        $keyboard[$i][$j]['action']['hash'] .= (isset($button[5])) ? "&description={$button[5]}" : '';
                        $keyboard[$i][$j]['action']['hash'] .= (isset($button[6])) ? "&data={$button[6]}" : '';
                        $keyboard[$i][$j]['action']['hash'] .= '&aid=1';
                        break;
                    }
                    case 'open_app': {
                        $keyboard[$i][$j]['action']['label'] = $button[2];
                        $keyboard[$i][$j]['action']['app_id'] = $button[3];
                        if(isset($button[4]))
                            $keyboard[$i][$j]['action']['owner_id'] = $button[4];
                        if(isset($button[5]))
                            $keyboard[$i][$j]['action']['hash'] = $button[5];
                        break;
                    }
                    case 'open_link': {
                        $keyboard[$i][$j]['action']['link'] = $button[2];
                        $keyboard[$i][$j]['action']['label'] = $button[3];
                    }
                }
                $j++;
            }
            $i++;
        }
        $keyboard = ['one_time' => $one_time, 'buttons' => $keyboard, 'inline' => $inline];
        $keyboard = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
        return $keyboard;
    }

    public function json_online($data) { //не работает
        $json = (is_array($data)) ? json_encode($data) : $data;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($json) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type:application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['name' => rand(10000000, 100000000), 'data' => $json]));
            print json_encode(['name' => rand(10000000, 100000000), 'data' => $json]);
        }
        curl_setopt($ch, CURLOPT_URL, 'https://jsoneditoronline.herokuapp.com/v1/docs/');
        $result = json_decode(curl_exec($ch), True);
        curl_close($ch);
        var_dump($result);
        return 'https://jsoneditoronline.org/?id=' . $result['id'];
    }

    public function getAllDialogs($extended = 0, $filter = 'all', $fields = null) {
        for ($count_all = 0, $offset = 0, $last_id = []; $offset <= $count_all; $offset += 199) {
            $members = $this->request('messages.getConversations', $last_id + ['count' => 200, 'filter' => $filter, 'extended' => $extended, 'fields' => (is_array($fields) ? join(',', $fields) : '')]);
            if ($count_all == 0)
                $count_all = $members['count'];
            if (empty($members['items']))
                break;
            foreach ($members['items'] as $item) {
                if (($last_id['start_message_id'] ?? 0) == $item['last_message']['id']) {
                    continue;
                } else
                    $last_id['start_message_id'] = $item['last_message']['id'];
                yield $item;
            }
        }
    }

    public function request($method, $params = []) {
        if (isset($params['message'])) {
            $params['message'] = $this->placeholders($params['peer_id'] ?? null, $params['message']);
            if ($this->is_test_len_str and mb_strlen($params['message']) > 544)
                $params['message'] = $this->lengthMessageProcessing($params['peer_id'] ?? null, $params['message']);
        }
        for ($iteration = 0; $iteration < 6; ++$iteration) {
            try {
                return $this->request_core($method, $params);
            } catch (SimpleVkException $e) {
                if (in_array($e->getCode(), $this->request_ignore_error)) {
                    sleep(1);
                    $iteration = 0;
                    continue;
                } else if ($e->getCode() == 5 and isset($this->auth) and $iteration != 0) {
                    $this->auth->reloadToken();
                    $this->token = $this->auth->getAccessToken();
                    continue;
                } else if ($e->getCode() == 77777) {
                    if ($iteration == 5) {
                        $error_message = "Запрос к вк вернул пустоту. Завершение 5 попыток отправки\n
                                  Метод:$method\nПараметры:\n" . json_encode($params);
                        throw new SimpleVkException(77777, $error_message);
                    }
                    continue;
                }
                throw new Exception($e->getMessage(), $e->getCode());
            }
        }
        return false;
    }

    protected function request_core($method, $params = []) {
        $params['access_token'] = $this->token;
        $params['v'] = $this->version;
        $params['random_id'] = random_int(-2147483648, 2147483647);
        $url = $this->api_url . $method;
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type:multipart/form-data'
            ]);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            if (isset(self::$proxy['ip'])) {
                curl_setopt($ch, CURLOPT_PROXYTYPE, self::$proxy_types[self::$proxy['type']]);
                curl_setopt($ch, CURLOPT_PROXY, self::$proxy['ip']);
                if (isset(self::$proxy['user_pwd'])) {
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, self::$proxy['user_pwd']);
                }
            }
            $result = json_decode(curl_exec($ch), True);
            curl_close($ch);
        } else {
            $result = json_decode(file_get_contents($url, true, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'content' => http_build_query($params)
                ]
            ])), true);
        }
        if (!isset($result)) {
            throw new SimpleVkException(77777, 'Запрос к вк вернул пустоту.');
        }
        if (isset($result['error'])) {
            throw new SimpleVkException($result['error']['error_code'], json_encode($result));
        }
        if (isset($result['response']))
            return $result['response'];
        else
            return $result;
    }

    protected function debugRun() {
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
    }

    protected function sendOK() {
        set_time_limit(0);
        ini_set('display_errors', 'Off');
        ob_end_clean();

        // для Nginx
        if (is_callable('fastcgi_finish_request')) {
            echo 'ok';
            session_write_close();
            fastcgi_finish_request();
            $this->debugRun();
            return True;
        }
        // для Apache
        ignore_user_abort(true);

        ob_start();
        header('Content-Encoding: none');
        header('Content-Length: 2');
        header('Connection: close');
        echo 'ok';
        ob_end_flush();
        flush();
        $this->debugRun();
        return True;
    }

    protected function processAuth($token, $version, $also_version) {
        if ($token instanceof auth) {
            $this->auth = $token;
            $this->version = $version;
            $this->token = $this->auth->getAccessToken();
        } else if (isset($also_version)) { //авторизация через аккаунт
            $this->auth = new Auth($token, $version);
            $this->token = $this->auth->getAccessToken();
            $this->version = $also_version;
        } else { //авторизация через токен
            $this->token = $token;
            $this->version = $version;
        }
    }
}