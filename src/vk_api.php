<?php

namespace DigitalStar\vk_api;

use CURLFile;

include('config_library.php');

class vk_api
{

    private $token = '';
    private $version = '';
    private $debug_mode = 0;
    private $data = [];
    private $action_version = 0;
    private $auth = null;
    private $request_ignore_error = REQUEST_IGNORE_ERROR;
    private $try_count_resend_file = COUNT_TRY_SEND_FILE;

    public static function create($token, $version, $also_version = null)
    {
        return new self($token, $version, $also_version);
    }

    public function __construct($token, $version, $also_version = null)
    {
        if ($token instanceof auth) {
            $this->auth = $token;
            $this->version = $version;
            $this->token = $this->auth->getAccessToken();
        } else if (isset($also_version)) {
            $this->auth = new Auth($token, $version);
            $this->token = $this->auth->getAccessToken();
            $this->version = $also_version;
        } else {
            $this->token = $token;
            $this->version = $version;
        }
        foreach (DIFFERENCE_VERSIONS_METHOD as $version => $methods) {
            if ($this->version >= $version) {
                $this->action_version = $version;
                break;
            }
        }
        $this->data = json_decode(file_get_contents('php://input'));
    }

    protected function copyAllDataclass()
    {
        return [$this->token, $this->version, $this->action_version, $this->auth, $this->request_ignore_error, $this->try_count_resend_file];
    }

    protected function setAllDataclass($id_vk_vars)
    {
        list($this->token, $this->version, $this->action_version, $this->auth, $this->request_ignore_error, $this->try_count_resend_file) = $id_vk_vars;
    }

    public function setConfirm($str)
    {
        if ($this->data->type == 'confirmation') { //Если vk запрашивает ключ
            exit($str); //Завершаем скрипт отправкой ключа
        }
    }

    /**
     * @throws ErrorCountArguments
     */
    public function initVars($selectors, &...$args)
    {
        if (!$this->debug_mode)
            $this->sendOK();
        $data = $this->data;
        
        if(isset($data->object->payload))
            $data->object->payload = json_decode($data->object->payload, true);
        
        $init = [
            'id' => $data->object->peer_id ?? null,
            'user_id' => $data->object->from_id ?? null,
            'message' => $data->object->text ?? null,
            'payload' => $data->object->payload ?? null,
            'type' => $this->data->type ?? null,
            'all' => $data,
        ];
        $selectors = explode(',', $selectors);
        if (count($selectors) != count($args))
            throw new VkApiException('Разное количество аргументов и переменных при инициализации');
        foreach ($selectors as $key => $val)
            $args[$key] = $init[trim($val)];
    }

    /**
     * @throws NoCallback
     */
    public function reply($message)
    {
        if ($this->data != []) {
            return $this->request('messages.send', ['message' => $message, 'peer_id' => $this->data->object->peer_id]);
        } else {
            throw new VkApiException('Вк не прислал callback, возможно вы пытаетесь запустить скрипт с локалки');
        }
    }

    public function sendAllDialogs($message)
    {
        $ids = [];
        for ($count_all = 1, $offset = 0; $offset <= $count_all; $offset += 200) {
            $members = $this->request('messages.getConversations', ['count' => 200, 'offset' => $offset]);
            if ($count_all != 1)
                $offset += $members['count'] - $count_all;

            $count_all = $members['count'];

            foreach ($members["items"] as $id) {
                $ids [] = $id['conversation']['peer']['id'];
                if (count($ids) == 100) {
                    try {
                        $this->request('messages.send', ['user_ids' => join(',', $ids), 'message' => $message]);
                    } catch (Exception $e) {
                    }
                    $ids = [];
                }
            }
            if ($ids != []) {
                try {
                    $this->request('messages.send', ['user_ids' => join(',', $ids), 'message' => $message]);
                } catch (Exception $e) {
                }
            }
        }
    }

    public function getAlias($id, $n = null)
    { //получить обращение к юзеру или группе
        if (!is_numeric($id)) { //если короткая ссылка
            $obj = $this->request('utils.resolveScreenName', ['screen_name' => $id]); //узнаем, кому принадлежит, сообществу или юзеру
            $id = ($obj["type"] == 'group') ? -$obj['object_id'] : $obj['object_id'];
        }
        if (isset($n)) {
            if(is_string($n)) {
                if ($id < 0)
                    return "@club".($id*-1)."($n)";
                else
                    return "@id{$id}($n)";
            } else {
                if ($id < 0) {
                    $id = -$id;
                    $group_name = $this->request('groups.getById', ['group_id' => $id])[0]['name'];
                    return "@club{$id}({$group_name})";
                } else {
                    $info = $this->userInfo($id);
                    if ($n)
                        return "@id{$id}($info[first_name] $info[last_name])";
                    else
                        return "@id{$id}($info[first_name])";
                }
            }
        } else {
            if ($id < 0)
                return "@club".($id*-1);
            else
                return "@id{$id}";
        }
    }

    /**
     * @throws NotAdminOrNotInside
     */
    public function isAdmin($chat_id, $user_id)
    { //возвращает привелегию по id
        try {
            $members = $this->request('messages.getConversationMembers', ['peer_id' => $chat_id])['items'];
        } catch (Exception $e) {
            throw new VkApiException('Бот не админ в этой беседе, или бота нет в этой беседе');
        }
        foreach ($members as $key) {
            if ($key['member_id'] == $user_id)
                return (isset($key["is_owner"])) ? 'owner' : (isset($key["is_admin"])) ? 'admin' : false;
        }
        return null;
    }

    public function sendMessage($id, $message)
    {
        if ($id != 0 and $id != '0') {
            return $this->request('messages.send', ['message' => $message, 'peer_id' => $id]);
        } else {
            return true;
        }
    }

    public function debug()
    {
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        echo 'ok';
        $this->debug_mode = 1;
    }

    public static function sendOK()
    {
        ini_set('display_errors', 'Off');
        echo 'ok';
        $response_length = ob_get_length();
        // check if fastcgi_finish_request is callable
        if (is_callable('fastcgi_finish_request')) {
            /*
             * This works in Nginx but the next approach not
             */
            session_write_close();
            fastcgi_finish_request();

            return True;
        }

        ignore_user_abort(true);

        ob_start();
        $serverProtocol = filter_input(INPUT_SERVER, 'SERVER_PROTOCOL', FILTER_SANITIZE_STRING);
        header($serverProtocol . ' 200 OK');
        header('Content-Encoding: none');
        header('Content-Length: ' . $response_length);
        header('Connection: close');

        ob_end_flush();
        ob_flush();
        flush();

        return True;
    }

    private function generateKeyboard($buttons = [], $one_time = False)
    {
        $keyboard = [];
        $i = 0;
        foreach ($buttons as $button_str) {
            $j = 0;
            foreach ($button_str as $button) {
                $color = $this->replaceColor($button[2]);
                $keyboard[$i][$j]["action"]["type"] = "text";
                if ($button[0] != null)
                    $keyboard[$i][$j]["action"]["payload"] = json_encode($button[0], JSON_UNESCAPED_UNICODE);
                $keyboard[$i][$j]["action"]["label"] = $button[1];
                $keyboard[$i][$j]["color"] = $color;
                $j++;
            }
            $i++;
        }
        $keyboard = ["one_time" => $one_time,
            "buttons" => $keyboard];
        $keyboard = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
        return $keyboard;
    }

    public function sendButton($user_id, $message, $buttons = [], $one_time = False)
    {
        $keyboard = $this->generateKeyboard($buttons, $one_time);
        return $this->request('messages.send', ['message' => $message, 'peer_id' => $user_id, 'keyboard' => $keyboard]);
    }

    private function getUploadServerMessages($peer_id, $selector = 'doc')
    {
        $result = null;
        if ($selector == 'doc')
            $result = $this->request('docs.getMessagesUploadServer', ['type' => 'doc', 'peer_id' => $peer_id]);
        else if ($selector == 'photo')
            $result = $this->request('photos.getMessagesUploadServer', ['peer_id' => $peer_id]);
        return $result;
    }

    private function getUploadServerPost($peer_id = [])
    {
        if ($peer_id < 0)
            $peer_id = ['group_id' => $peer_id * -1];
        else
            $peer_id = [];
        $result = $this->request('docs.getUploadServer', $peer_id);
        return $result;
    }

    private function getWallUploadServer($id)
    {
        if ($id < 0) {
            $id *= -1;
            return $this->request('photos.getWallUploadServer', ['group_id' => $id]);
        } else {
            return $this->request('photos.getWallUploadServer', ['user_id' => $id]);
        }
    }

    private function savePhoto($photo, $server, $hash)
    {
        return $this->request('photos.saveMessagesPhoto', ['photo' => $photo, 'server' => $server, 'hash' => $hash]);
    }

    private function savePhotoWall($photo, $server, $hash, $id)
    {
        if ($id < 0) {
            $id *= -1;
            return $this->request('photos.saveWallPhoto', ['photo' => $photo, 'server' => $server, 'hash' => $hash, 'group_id' => $id]);
        } else {
            return $this->request('photos.saveWallPhoto', ['photo' => $photo, 'server' => $server, 'hash' => $hash, 'user_id' => $id]);
        }
    }

    public function groupInfo($group_url)
    {
        $group_url = preg_replace("!.*?/!", '', $group_url);
        return current($this->request('groups.getById', ["group_ids" => $group_url]));
    }

    public function userInfo($user_url = null, $scope = [])
    {
        if (isset($scope) and count($scope) != 0)
            $scope = ["fields" => join(",", $scope)];
        if (isset($user_url)) {
            $user_url = preg_replace("!.*?/!", '', $user_url);
            return current($this->request('users.get', ["user_ids" => $user_url] + $scope));
        } else
            return current($this->request('users.get', [] + $scope));
    }

    protected function editRequestParams($method, $params)
    {
        return [$method, $params];
    }

    /**
     * @throws Unknow_error
     */
    public function request($method, $params = [])
    {
        list($method, $params) = $this->editRequestParams($method, $params);
        $url = 'https://api.vk.com/method/' . $method;
        $params['access_token'] = $this->token;
        $params['v'] = $this->version;
        $params += $this->differenceVersions($method);

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
    }

    private function request_core($url, $params = [])
    {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type:multipart/form-data"
            ]);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
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
        if (!isset($result) or isset($result['error']))
            throw new VkApiException(json_encode($result));
        if (isset($result['response']))
            return $result['response'];
        else
            return $result;
    }

    private function replaceColor($color)
    {
        switch ($color) {
            case 'red':
                $color = 'negative';
                break;
            case 'green':
                $color = 'positive';
                break;
            case 'white':
                $color = 'default';
                break;
            case 'blue':
                $color = 'primary';
                break;
        }
        return $color;
    }

    private function sendFiles($url, $local_file_path, $type = 'file')
    {
        $post_fields = [
            $type => new CURLFile(realpath($local_file_path))
        ];

        for ($i = 0; $i < $this->try_count_resend_file; ++$i) {

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type:multipart/form-data"
            ]);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
            $output = curl_exec($ch);
            if ($output != '')
                break;
            else
                sleep(1);
        }
        if ($output == '')
            throw new VkApiException('Не удалось загрузить файл на сервер');
        return $output;
    }

    private function uploadImage($id, $local_file_path)
    {
        $upload_url = $this->getUploadServerMessages($id, 'photo')['upload_url'];
        for ($i = 0; $i < $this->try_count_resend_file; ++$i) {
            try {
                $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path, 'photo'), true);
                return $this->savePhoto($answer_vk['photo'], $answer_vk['server'], $answer_vk['hash']);
            } catch (VkApiException $e) {
                sleep(1);
                $exception = json_decode($e->getMessage(), true);
                if ($exception['error']['error_code'] != 121)
                    throw new VkApiException($e->getMessage());
            }
        }
        $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path, 'photo'), true);
        return $this->savePhoto($answer_vk['photo'], $answer_vk['server'], $answer_vk['hash']);
    }

    public function sendImage($id, $local_file_path)
    {
        $upload_file = $this->uploadImage($id, $local_file_path);
        return $this->request('messages.send', ['attachment' => "photo" . $upload_file[0]['owner_id'] . "_" . $upload_file[0]['id'], 'peer_id' => $id]);
    }

    private function uploadDocsMessages($id, $local_file_path, $title = null)
    {
        if (!isset($title))
            $title = preg_replace("!.*?/!", '', $local_file_path);
        $upload_url = $this->getUploadServerMessages($id)['upload_url'];
        $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path), true);
        $upload_file = $this->saveDocuments($answer_vk['file'], $title);
        return $upload_file;
    }

    private function uploadDocs($id, $local_file_path, $title = null)
    {
        if (!isset($title))
            $title = preg_replace("!.*?/!", '', $local_file_path);
        $upload_url = $this->getUploadServerPost($id)['upload_url'];
        $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path), true);
        $upload_file = $this->saveDocuments($answer_vk['file'], $title);
        return $upload_file;
    }

    public function uploadDocsGroup($groupID, $local_file_path, $title = null)
    {
        return $this->uploadDocs($groupID, $local_file_path, $title);
    }

    public function uploadDocsUser($local_file_path, $title = null)
    {
        return $this->uploadDocs([], $local_file_path, $title);
    }

    public function sendDocMessage($id, $local_file_path, $title = null)
    {
        $upload_file = current($this->uploadDocsMessages($id, $local_file_path, $title));
        if ($id != 0 and $id != '0') {
            return $this->request('messages.send', ['attachment' => "doc" . $upload_file['owner_id'] . "_" . $upload_file['id'], 'peer_id' => $id]);
        } else {
            return true;
        }
    }

    private function saveDocuments($file, $title)
    {
        return $this->request('docs.save', ['file' => $file, 'title' => $title]);
    }

    public function createPost($id, $message = [], $props = [], $media = [])
    {
        $send_attachment = [];

        foreach ($media as $selector => $massive) {
            switch ($selector) {
                case "images":
                    foreach ($massive as $image) {
                        $upload_url = $this->getWallUploadServer($id);
                        for ($i = 0; $i <= $this->try_count_resend_file; ++$i) {
                            try {
                                $answer_vk = json_decode($this->sendFiles($upload_url['upload_url'], $image, 'photo'), true);
                                $upload_file = $this->savePhotoWall($answer_vk['photo'], $answer_vk['server'], $answer_vk['hash'], $id);
                                break;
                            } catch (VkApiException $e) {
                                if ($i == $this->try_count_resend_file)
                                    throw new VkApiException($e->getMessage());
                                sleep(1);
                                $exception = json_decode($e->getMessage(), true);
                                if ($exception['error']['error_code'] != 121)
                                    throw new VkApiException($e->getMessage());
                            }
                        }
                        $send_attachment[] = "photo" . $upload_file[0]['owner_id'] . "_" . $upload_file[0]['id'];
                    }
                    break;
                case "docs":
                    foreach ($massive as $docs) {
                        $upload_file = $this->uploadDocsUser($docs);
                        if (isset($upload_file['type']))
                            $upload_file = $upload_file[$upload_file['type']];
                        else
                            $upload_file = current($upload_file);
                        $send_attachment[] = "doc" . $upload_file['owner_id'] . "_" . $upload_file['id'];
                    }
                    break;
                case "other":
                    break;
            }
        }
        if (count($send_attachment) != 0)
            $send_attachment = ["attachment" => join(',', $send_attachment)];
        if (is_string($message))
            $message = ['message' => $message];
        return $this->request('wall.post', ['owner_id' => $id] + $message + $props + $send_attachment);
    }

    public function createMessages($id, $message = [], $props = [], $media = [], $keyboard = [])
    {
        $send_attachment = [];

        foreach ($media as $selector => $massiv) {
            switch ($selector) {
                case "images":
                    foreach ($massiv as $image) {
                        $upload_file = $upload_file = $this->uploadImage($id, $image);
                        $send_attachment[] = "photo" . $upload_file[0]['owner_id'] . "_" . $upload_file[0]['id'];
                    }
                    break;
                case "docs":
                    foreach ($massiv as $document) {
                        $upload_file = $this->uploadDocsMessages($id, $document['path'], $document['title']);
                        if (isset($upload_file['type']))
                            $upload_file = $upload_file[$upload_file['type']];
                        else
                            $upload_file = current($upload_file);
                        $send_attachment[] = "doc" . $upload_file['owner_id'] . "_" . $upload_file['id'];
                    }
                    break;
                case "other":
                    break;
            }
        }
        if (count($send_attachment) != 0)
            $send_attachment = ["attachment" => join(',', $send_attachment)];
        if (is_string($message))
            $message = ['message' => $message];
        if ($keyboard != [])
            $keyboard = ['keyboard' => $this->generateKeyboard($keyboard['keyboard'], $keyboard['one_time'])];
        return $this->request('messages.send', ['peer_id' => $id] + $message + $props + $send_attachment + $keyboard);
    }

    public function getGroupsUser($id = [], $extended = 1, $props = [])
    {
        if (is_numeric($id))
            $id = ['user_id' => $id];
        if (!is_array($props))
            $props = [];
        if ($extended == 1)
            $extended = ['extended' => 1];
        else
            $extended = [];
        return $this->request('groups.get', $id + $props + $extended);
    }

    public function setTryCountResendFile($var)
    {
        if (is_integer($var))
            $this->try_count_resend_file = $var;
        else
            throw new VkApiException("Параметр должен быть числовым");
    }

    public function setRequestIgnoreError($var)
    {
        if (is_array($var))
            $this->request_ignore_error = $var;
        else if (is_integer($var))
            $this->request_ignore_error = [$var];
        else
            throw new VkApiException("Параметр должен быть числовым либо массивом");
    }

    private function differenceVersions($method)
    {
        if (array_key_exists($this->action_version, DIFFERENCE_VERSIONS_METHOD) and array_key_exists($method, DIFFERENCE_VERSIONS_METHOD[$this->action_version]))
            $extra_props = DIFFERENCE_VERSIONS_METHOD[$this->action_version][$method];
        else
            $extra_props = [];
        foreach ($extra_props as $key => $value) {
            if (strpos($value, "%RANDOMIZE_INT32%") !== false)
                $extra_props[$key] = str_replace("%RANDOMIZE_INT32%", rand(-2147483648, 2147483647), $value);
        }
        return $extra_props;
    }
}









