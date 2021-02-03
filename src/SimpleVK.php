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
    protected $group_id = null;

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

    public static function disableSendOK() {
        self::$debug_mode = true;
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

    public function msg($text = null) {
        return Message::create($this)->text($text);
    }

    public function isAdmin($user_id, $chat_id) { //возвращает привелегию по id
        try {
            $members = $this->request('messages.getConversationMembers', ['peer_id' => $chat_id])['items'];
        } catch (Exception $e) {
            throw new SimpleVkException(0, 'Бот не админ в этой беседе, или бота нет в этой беседе');
        }
        foreach ($members as $key) {
            if ($key['member_id'] == $user_id)
                return (isset($key["is_owner"])) ? 'owner' : ((isset($key["is_admin"])) ? 'admin' : false);
        }
        return null;
    }

    public function initPeerID(&$id) {
        $id = $this->data['object']['peer_id'] ?? null;
        return $this;
    }

    public function initText(&$text) {
        $text = $this->data['object']['text'] ?? null;
        return $this;
    }

    public function initPayload(&$payload) {
        $payload = $this->getPayload();
        return $this;
    }

    public function initUserID(&$user_id) {
        $user_id = $this->data['object']['from_id'] ?? $this->data['object']['user_id'] ?? null;
        return $this;
    }

    public function initType(&$type) {
        $type = $this->data['type'] ?? null;
        return $this;
    }

    public function initData(&$data) {
        $data = $this->data_backup;
        return $this;
    }

    public function initMsgID(&$id) {
        $id = $this->data['object']['id'] ?? null;
        return $this;
    }

    public function initConversationMsgID(&$id) {
        $id = $this->data['object']['conversation_message_id'] ?? null;
        return $this;
    }

    public function getAttachments() {
        $data = $this->data;
        if (!isset($data['object']['attachments']))
            return null;
        $result = [];
        if(isset($data['object']['attachments']['attach1_type'])) //TODO временная заглушка для user longpoll
            return null;
        foreach ($data['object']['attachments'] as $key => $attachment) {
            if($key == 'attach1_type') //TODO временная заглушка для user longpoll
                return null;
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

    public function initVars(&$id = null, &$user_id = null, &$type = null, &$message = null, &$payload = null, &$msg_id = null, &$attachments = null) {
        $data = $this->data;
        $type = $data['type'] ?? null;
        $id = $data['object']['peer_id'] ?? null;
        $message = $data['object']['text'] ?? null;
        $user_id = $data['object']['from_id'] ?? $data['object']['user_id'] ?? null;
        $msg_id = $data['object']['id'] ?? null;
        $payload = $this->getPayload();
        $attachments = $this->getAttachments();
        return $this->data_backup;
    }

    public function clientSupport(&$keyboard, &$inline, &$buttons) {
        $data = $this->data_backup['object']['client_info'];
        $keyboard = $data['keyboard'];
        $inline = $data['inline_keyboard'];
        $buttons = $data['button_actions'];
        return $data;
    }

    public function sendAllDialogs(Message $message) {
        $ids = [];
        $i = 0;
        $count = 0;
        $members = $this->request('messages.getConversations', ['count' => 1])['count'];
        foreach ($this->getAllDialogs() as $dialog) {
            if ($dialog['conversation']['can_write']['allowed']) {
                $user_id = $dialog['conversation']['peer']['id'];
                if ($user_id < 2e9) {
                    $ids[] = $user_id;
                    $i++;
                }
            }
            if ($i == 100) {
                $return = $message->send($ids);
                $i = 0;
                $ids = [];
                $current_count = count(array_column($return, 'message_id'));
                $count += $current_count;
                print "Отправлено $count/$members" . PHP_EOL;
            }
        }
        $return = $message->send($ids);
        $current_count = count(array_column($return, 'message_id'));
        $count += $current_count;
        print "Всего было отправлено $count/$members сообщений" . PHP_EOL;
        print "Запретили отправлять сообщения " . ($members - $count) . " человек(либо это были чаты)";
    }

    public function sendAllChats(Message $message) {
        $message->uploadAllImages();
        $count = 0;
        print "Начинаю рассылку\n";
        for ($i = 1; ; $i = $i + 100) {
            $return = $message->send(range(2e9 + $i, 2e9 + $i + 99));
            $current_count = count(array_column($return, 'message_id'));
            $count += $current_count;
            print "Отправлено $count" . PHP_EOL;
            if ($current_count != 100) {
                print "Всего было разослано в $count бесед";
                break;
            }
        }
    }

    public function eventAnswerSnackbar($text) {
        $this->checkTypeEvent();
        $this->request('messages.sendMessageEventAnswer', [
            'event_id' => $this->data['object']['event_id'],
            'user_id' => $this->data['object']['user_id'],
            'peer_id' => $this->data['object']['peer_id'],
            'event_data' => json_encode([
                'type' => 'show_snackbar',
                'text' => $text
            ])
        ]);
    }

    public function eventAnswerOpenLink($url) {
        $this->checkTypeEvent();
        $this->request('messages.sendMessageEventAnswer', [
            'event_id' => $this->data['object']['event_id'],
            'user_id' => $this->data['object']['user_id'],
            'peer_id' => $this->data['object']['peer_id'],
            'event_data' => json_encode([
                'type' => 'open_link',
                'link' => $url
            ])
        ]);
    }

    public function eventAnswerOpenApp($app_id, $owner_id = null, $hash = null) {
        $this->checkTypeEvent();
        $this->request('messages.sendMessageEventAnswer', [
            'event_id' => $this->data['object']['event_id'],
            'user_id' => $this->data['object']['user_id'],
            'peer_id' => $this->data['object']['peer_id'],
            'event_data' => json_encode([
                'type' => 'open_app',
                'app_id' => $app_id,
                'owner_id' => $owner_id,
                'hash' => $hash
            ])
        ]);
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

    public function sendWallComment($owner_id, $post_id, $message) {
        return $this->request('wall.createComment', ['owner_id' => $owner_id, 'post_id' => $post_id, 'message' => $message]);
    }

    public function dateRegistration($id) {
        $site = file_get_contents("https://vk.com/foaf.php?id={$id}");
        preg_match('<ya:created dc:date="(.*?)">', $site, $data);
        $data = explode('T', $data[1]);
        $date = date("d.m.Y", strtotime($data[0]));
        $time = mb_substr($data[1], 0, 8);
        return "$time $date";
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

    public function buttonCallback($text, $color = 'white', $payload = null) {
        return ['callback', $payload, $text, self::$color_replacer[$color]];
    }

    static $color_replacer = [
        'blue' => 'primary',
        'white' => 'default',
        'red' => 'negative',
        'green' => 'positive'
    ];

    public function json_online($data = null) {
        if (is_null($data))
            $data = $this->data;
        $json = is_array($data) ? json_encode($data) : $data;
        $name = time() . random_int(-2147483648, 2147483647);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($json) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type:application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['name' => $name, 'data' => $json]));
        }
        curl_setopt($ch, CURLOPT_URL, 'https://jsoneditoronline.herokuapp.com/v1/docs/');
        $result = json_decode(curl_exec($ch), True);
        curl_close($ch);
        return 'https://jsoneditoronline.org/?id=' . $result['id'];
    }

    public function groupInfo($group_url = null) {
        if (!is_null($group_url))
            $group_url = ['group_ids' => preg_replace("!.*?/!", '', $group_url)];
        else
            $group_url = [];
        return current($this->request('groups.getById', $group_url));
    }

    public function getAllDialogs($extended = 0, $filter = 'all', $fields = null) {
        for ($count_all = 0, $offset = 0, $last_id = []; $offset <= $count_all; $offset += 199) {
            $members = $this->request('messages.getConversations', $last_id + [
                    'count' => 200,
                    'filter' => $filter,
                    'extended' => $extended,
                    'fields' => (is_array($fields) ? join(',', $fields) : '')]);
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

    public function getAllComments($owner_id_ir_url, $post_id = null, $extended = 0, $sort = 'asc', $fields = null) {
        if (!is_numeric($owner_id_ir_url) and is_null($post_id)) {
            if (preg_match("!(-?\d+)_(\d+)!", $owner_id_ir_url, $matches)) {
                $owner_id_ir_url = $matches[1];
                $post_id = $matches[2];
            } else
                throw new SimpleVkException(0, "Передайте 2 параметра (id пользователя, id поста), или корректную ссылку на пост");
        }
        for ($count_all = 0, $offset = 0, $last_id = []; $offset <= $count_all; $offset += 99) {
            $members = $this->request('wall.getComments', $last_id + [
                    'count' => 100,
                    'owner_id' => $owner_id_ir_url,
                    'post_id' => $post_id,
                    'extended' => $extended,
                    'sort' => $sort,
                    'fields' => (is_array($fields) ? join(',', $fields) : '')]);
            if ($count_all == 0)
                $count_all = $members['count'];
            if (empty($members['items']))
                break;
            foreach ($members['items'] as $item) {
                if (($last_id['start_comment_id'] ?? 0) == $item['id']) {
                    continue;
                } else
                    $last_id['start_comment_id'] = $item['id'];
                yield $item;
            }
        }
    }

    public function getAllMembers($group_id = null, $filter = null, $fields = null, $sort = null) {
        if (is_null($group_id))
            $group_id = $this->groupInfo()['id'];
        return $this->generatorRequest('groups.getMembers', [
                'fields' => (is_array($fields) ? join(',', $fields) : ''),
                'group_id' => $group_id]
            + ($filter ? ['filter' => $filter] : [])
            + ($sort ? ['sort' => $sort] : []), 1000);
    }

    public function getAllGroupsFromUser($user_id = null, $extended = 0, $filter = null, $fields = null) {
        $extended = (!is_null($fields) || $extended);
        return $this->generatorRequest('groups.get', [
                'extended' => $extended]
            + ($filter ? ['filter' => $filter] : [])
            + ($fields ? ['fields' => $fields] : [])
            + ($user_id ? ['user_id' => $user_id] : []), 1000);
    }

    public function getAllWalls($id = null, $extended = 0, $filter = null, $fields = null) {
        $extended = (!is_null($fields) || $extended);
        return $this->generatorRequest('wall.get', [
                'extended' => $extended]
            + ($filter ? ['filter' => $filter] : [])
            + ($fields ? ['fields' => $fields] : [])
            + ($id ? ['owner_id' => $id] : []), 100);
    }

    public function generatorRequest($method, $params, $count = 200) {
        for ($count_all = 0, $offset = 0; $offset <= $count_all; $offset += $count) {
            $members = $this->request($method, $params + ['offset' => $offset, 'count' => $count]);
            if ($count_all == 0)
                $count_all = $members['count'];
            foreach ($members['items'] as $item)
                yield $item;
        }
    }

    public function responseGeneratorRequest($method, $params, $count = 200) {
        for ($count_all = 0, $offset = 0; $offset <= $count_all; $offset += $count) {
            $members = $this->request($method, $params + ['offset' => $offset, 'count' => $count]);
            if ($count_all == 0)
                $count_all = $members['count'];
            yield $members;
        }
    }

    public function group($id = null) {
        $this->group_id = $id;
        return $this;
    }

    public function request($method, $params = []) {
        if (isset($params['message'])) {
            $params['message'] = $this->placeholders($params['message'], $params['peer_id'] ?? null);
            if ($this->is_test_len_str and mb_strlen($params['message']) > 544)
                $params['message'] = $this->lengthMessageProcessing($params['peer_id'] ?? null, $params['message']);
        }
        if(isset($params['peer_id']) && is_array($params['peer_id'])) { //возможно везде заменить на peer_ids в методах
            $params['peer_ids'] = join(',',$params['peer_id']);
            unset($params['peer_id']);
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

    protected function getPayload() {
        if (isset($this->data['object']['payload'])) {
            if (is_string($this->data['object']['payload'])) {
                $payload = json_decode($this->data['object']['payload'], true) ?? $this->data['object']['payload'];
            } else
                $payload = $this->data['object']['payload'];
        } else
            $payload = null;
        return $payload;
    }

    public function placeholders($message, $id = null) {
        if ($id >= 2e9) {
            $id = $this->data['object']['from_id'] ?? null;
        }
        if (strpos($message, '~') !== false) {
            $message = preg_replace_callback(
                "|~(.*?)~|",
                function ($matches) use ($id) {
                    $ex1 = explode('|', $matches[1]);
                    if(isset($ex1[1])) {
                        $id = $ex1[1];
                    }
                    $tag = ['!fn', '!ln', '!full', 'fn', 'ln', 'full'];
                    if(in_array($ex1[0], $tag) && !$id) {
                        return $matches[1];
                    } else if(in_array($ex1[0], $tag) && $id) {
                        if($id >= 0) {
                            $data = $this->userInfo($id);
                            $f = $data['first_name'];
                            $l = $data['last_name'];
                            $replace = ["@id{$id}($f)", "@id{$id}($l)", "@id{$id}($f $l)", $f, $l, "$f $l"];
                            return str_replace($tag, $replace, $ex1[0]);
                        } else {
                            $id = -$id;
                            $group_name = $this->request('groups.getById', ['group_id' => $id])[0]['name'];
                            return "@club{$id}({$group_name})";
                        }
                    } else {
                        if($id >= 0) {
                            return "@id$id($ex1[0])";
                        } else {
                            $id = -$id;
                            return "@club$id($ex1[0])";
                        }
                    }

                }, $message);
            return $message;
        } else
            return $message;
    }

    protected function lengthMessageProcessing($id, $str) {
        $bytes = 0;
        $tmp_str = '';
        $this->is_test_len_str = false;
        $anon = function ($a) use (&$tmp_str, &$bytes, $id) {
            $byte = strlen((@iconv('UTF-8', 'cp1251', $a[0]))
                ?: "$#" . (unpack('V', iconv('UTF-8', 'UCS-4LE', $a[0]))[1]) . ";");
            $bytes += $byte;
            if ($bytes > 4096) {
                $this->request('messages.send', ['message' => $tmp_str, 'peer_id' => $id]); // Отправка части сообщения
                $bytes = $byte;
                $tmp_str = $a[0];
            } else
                $tmp_str .= $a[0];
            return "";
        };
        preg_replace_callback('/./us', $anon, $str);
        $this->is_test_len_str = true;
        return $tmp_str;
    }

    protected function checkTypeEvent() {
        if ($this->data['type'] != 'message_event')
            throw new SimpleVkException(0, "eventAnswerSnackbar можно использовать только при событии message_event");
    }

    protected function request_core($method, $params = []) {
        $params['access_token'] = $this->token;
        $params['v'] = $this->version;
        $params['random_id'] = random_int(-2147483648, 2147483647);
        if (!is_null($this->group_id) and empty($params['group_id']))
            $params['group_id'] = $this->group_id;
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
            throw new SimpleVkException(77777, 'Curl недоступен. Не удается выполнить запрос');
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

        PHP_EOL;
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
