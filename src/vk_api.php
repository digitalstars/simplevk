<?php

namespace src;
use src\VkApiException as VkApiException;

class vk_api
{

    private $token = '';
    private $version = '';

    public function __construct($token, $version)
    {
        $this->token = $token;
        $this->version = $version;
    }

    public function sendMessage($id, $message)
    {
        if ($id != 0 and $id != '0') {
            return $this->request('messages.send', ['message' => $message, 'peer_id' => $id]);
        } else {
            return true;
        }
    }

    public function sendOK()
    {
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

    private function getUploadServer($peer_id, $selector = 'doc')
    {
        $result = null;
        if ($selector == 'doc')
            $result = $this->request('docs.getMessagesUploadServer', ['type' => 'doc', 'peer_id' => $peer_id]);
        else if ($selector == 'photo')
            $result = $this->request('photos.getMessagesUploadServer', ['peer_id' => $peer_id]);
        return $result;
    }

    public function getWallUploadServer($id)
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
    
    public function request($method, $params = [])
    {
        $url = 'https://api.vk.com/method/' . $method;
        $params['access_token'] = $this->token;
        $params['v'] = $this->version;
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
            case 'red':   $color = 'negative'; break;
            case 'green': $color = 'positive'; break;
            case 'white': $color = 'default'; break;
            case 'blue':  $color = 'primary'; break;
        }
        return $color;
    }

    private function sendFiles($url, $local_file_path, $type = 'file')
    {
        $post_fields = [
            $type => new CURLFile(realpath($local_file_path))
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type:multipart/form-data"
        ]);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        $output = curl_exec($ch);
        if ($output == '')
            throw new VkApiException('Не удалось загрузить файл на сервер');
        return $output;
    }

    private function uploadImage($id, $local_file_path)
    {
        $upload_url = $this->getUploadServer($id, 'photo')['upload_url'];
        $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path, 'photo'), true);
        $upload_file = $this->savePhoto($answer_vk['photo'], $answer_vk['server'], $answer_vk['hash']);
        return $upload_file;
    }

    public function sendImage($id, $local_file_path)
    {
        $upload_file = $this->uploadImage($id, $local_file_path);
        return $this->request('messages.send', ['attachment' => "photo" . $upload_file[0]['owner_id'] . "_" . $upload_file[0]['id'], 'peer_id' => $id]);
    }

    private function uploadDocs($id, $local_file_path, $title = null)
    {
        if (!isset($title))
            $title = preg_replace("!.*?/!", '', $local_file_path);
        $upload_url = $this->getUploadServer($id)['upload_url'];
        $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path), true);
        $upload_file = $this->saveDocuments($answer_vk['file'], $title);
        return $upload_file;
    }

    public function sendDocMessage($id, $local_file_path, $title = null)
    {
        $upload_file = current($this->uploadDocs($id, $local_file_path, $title));
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

    public function createPost($id, $message = null, $props = [], $media = [])
    {
        $send_attachment = [];

        foreach ($media as $selector => $massive) {
            switch ($selector) {
                case "images":
                    foreach ($massive as $image) {
                        $upload_url = $this->getWallUploadServer($id);
                        $answer_vk = json_decode($this->sendFiles($upload_url['upload_url'], $image, 'photo'), true);
                        $upload_file = $this->savePhotoWall($answer_vk['photo'], $answer_vk['server'], $answer_vk['hash'], $id);
                        $send_attachment[] = "photo" . $upload_file[0]['owner_id'] . "_" . $upload_file[0]['id'];
                    }
                    break;
                case "docs":
                    break;
                case "other":
                    break;
            }
        }
        if (count($send_attachment) != 0)
            $send_attachment = ["attachment" => join(',', $send_attachment)];
        if (isset($message))
            $message = ['message' => $message];
        return $this->request('wall.post', ['owner_id' => $id] + $message + $props + $send_attachment);
    }

    public function createMessages($id, $message = null, $props = [], $media = [], $keyboard = [])
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
                        $upload_file = current($this->uploadDocs($id, $document));
                        $send_attachment[] = "doc" . $upload_file['owner_id'] . "_" . $upload_file['id'];
                    }
                    break;
                case "other":
                    break;
            }
        }
        if (count($send_attachment) != 0)
            $send_attachment = ["attachment" => join(',', $send_attachment)];
        if (isset($message))
            $message = ['message' => $message];
        if (isset($keyboard))
            $keyboard = ['keyboard' => $this->generateKeyboard($keyboard['keyboard'], $keyboard['one_time'])];
        return $this->request('messages.send', ['peer_id' => $id] + $message + $props + $send_attachment + $keyboard);
    }

}









