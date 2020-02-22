<?php
namespace DigitalStars\simplevk;

class LongPoll extends SimpleVK {
    private $group_id;
    private $key;
    private $server;
    private $ts;
    private $auth_type;
    public static $use_user_long_poll = 0;

    public function __construct($token, $version, $also_version = null) {
        $this->processAuth($token, $version, $also_version);
        $data = $this->userInfo();
        if ($data != false || self::$use_user_long_poll) {
            $this->auth_type = 'user';
        } else {
            $this->auth_type = 'group';
            $this->group_id = $this->request('groups.getById')[0]['id'];
            $this->request('groups.setLongPollSettings', [
                'group_id' => $this->group_id,
                'enabled' => 1,
                'api_version' => $this->version,
                'message_new' => 1,
            ]);
        }
        $this->getLongPollServer();
    }

    public function listen($anon) {
        while ($data = $this->processingData()) {
            foreach ($data['updates'] as $event) {
                unset($this->data);
                unset($this->data_backup);
                $this->data = $event;
                $this->data_backup = $this->data;
                if ($this->auth_type == 'group') {
                    if (isset($this->data['object']['message']) and $this->data['type'] == 'message_new') {
                        $this->data['object'] = $this->data['object']['message'];
                    }
                    $anon($event);
                } else {
                    $this->userLongPoll($anon);
                }
            }
//            if ($this->vk instanceof Execute) {
//                $this->vk->exec();
//            }
        }
    }

    private function getLongPollServer() {
        if ($this->auth_type == 'user')
            $data = $this->request('messages.getLongPollServer', ['need_pts' => 1, 'lp_version' => 10]);
        else
            $data = $this->request('groups.getLongPollServer', ['group_id' => $this->group_id]);
        unset($this->key);
        unset($this->server);
        unset($this->ts);
        list($this->key, $this->server, $this->ts) = [$data['key'], $data['server'], $data['ts']];
    }

    private function processingData() {
        while ($data = $this->getData()) {
            if (isset($data['failed'])) {
                switch ($data['failed']) {
                    case 1:
                        unset($this->ts);
                        $this->ts = $data['ts'];
                        break;
                    case 2:
                    case 3:
                        $this->getLongPollServer();
                        break;
                }
                continue;
            } else {
                unset($this->ts);
                $this->ts = $data['ts'];
                return $data;
            }
        }
    }

    private function getData() {
        $default_params = ['act' => 'a_check', 'key' => $this->key, 'ts' => $this->ts, 'wait' => 25];
        try {
            if ($this->auth_type == 'user') {
                $params = ['mode' => 490, 'version' => 10];
                $data = $this->request_core_lp('https://' . $this->server . '?', $default_params + $params);
            } else {
                $data = $this->request_core_lp($this->server . '?', $default_params);
            }
            return $data;
        } catch (\Exception $e) {
            $this->sendErrorUser($e);
            throw new SimpleVkException($e->getCode(), $e->getMessage());
        }
    }

    private function request_core_lp($url, $params = [], $iteration = 1) {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url . http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            if(isset(self::$proxy['ip'])) {
                curl_setopt($ch, CURLOPT_PROXYTYPE, self::$proxy_types[self::$proxy['type']]);
                curl_setopt($ch, CURLOPT_PROXY, self::$proxy['ip']);
                if(isset(self::$proxy['user_pwd'])) {
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, self::$proxy['user_pwd']);
                }
            }
            $result = json_decode(curl_exec($ch), true);
            curl_close($ch);
        } else {
            $result = json_decode(file_get_contents($url . http_build_query($params), true, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n"
                ]
            ])), true);
        }
        if (!isset($result)) {
            if ($iteration <= 5) {
                SimpleVkException::nullError('Запрос к вк вернул пустоту. Повторная отправка, попытка №' . $iteration);
                $result = $this->request_core_lp($url, $params, ++$iteration); //TODO рекурсия
            } else {
                $error_message = "Запрос к вк вернул пустоту. Завершение 5 попыток отправки\n
                                  Метод:$url\nПараметры:\n" . json_encode($params);
                SimpleVkException::nullError($error_message);
                throw new \Exception($error_message, 77777);
            }
        }
        return $result;
    }

    private function userLongPoll($anon) {
        $data = $this->data;
        switch ($data[0]) {
            case 4:
            { //входящее сообщение
                if (!$this->checkFlags(2)) { //не обрабатывать, если это исходящее
                    $this->data = [];
                    $this->data['object']['id'] = isset($data[1]) ? $data[1] : null;
                    $this->data['object']['peer_id'] = isset($data[3]) ? $data[3] : null;
                    $this->data['object']['date'] = isset($data[4]) ? $data[4] : null;
                    $this->data['object']['text'] = isset($data[5]) ? $data[5] : null;
                    $this->data['object']['attachments'] = isset($data[7]) ? $data[7] : null;
                    $this->data['object']['random_id'] = (isset($data[8]) && !empty($data[8])) ? $data[8] : null;
                    $this->data['object']['conversation_message_id'] = isset($data[9]) ? $data[9] : null;
                    $this->data['object']['from_id'] = isset($data[6]['from']) ? $data[6]['from'] : $this->data['object']['peer_id'];
                    $this->data['type'] = 'message_new';
                    $this->data_backup = $this->data;
                    $anon($data);
                }
                break;
            }
            default:
            {
                $anon($data);
            }
        }
    }

    private function checkFlags($flag) {
        $all = [];
        $data = $this->data;
        foreach ([1, 2, 4, 8, 16, 32, 64, 128, 256, 512, 65536] as $key) {
            if ($data[2] & $key)
                $all[] = $key;
        }
        return (!in_array($flag, $all)) ? false : true;
    }
}