<?php

namespace DigitalStars\SimpleVK;

use DigitalStars\SimpleVK\SimpleVK as vk;

class Streaming {
    private $rules_url;
    private $stream_url;
    private $token;
    private $version;
    private $socket;
    private $stream_query;

    public function __construct($token, $version) {
        if (!function_exists('curl_init')) {
            exit('Для работы streaming небоходим curl. Прекращение работы');
        }
        $this->token = $token;
        $this->version = $version;
        $this->updateStreamingServer();
        $this->connect();
    }

    private function updateStreamingServer() {
        $response = $this->request("https://api.vk.com/method/streaming.getServerUrl?v=$this->version&access_token=$this->token", 'GET')['response'];
        $this->rules_url = "https://$response[endpoint]/rules?key=$response[key]";
        $this->stream_url = "ssl://$response[endpoint]:443";
        $this->stream_query = "/stream?key=$response[key]";
    }

    public function getRules() {
        return $this->request($this->rules_url, 'GET')['rules'];
    }

    public function addRule($value, $tag) {
        $json = ['rule' => ['value' => $value, 'tag' => $tag]];
        return $this->request($this->rules_url, 'POST', $json);
    }

    public function delRule($tag) {
        $json = ['tag' => $tag];
        return $this->request($this->rules_url, 'DELETE', $json);
    }

    public function delAllRules() {
        foreach ($this->getRules() as $item)
            $this->delRule($item['tag']);
        return true;
    }

    public function listen($anon) {
        while (true) {
            $data = $this->read(2);
            $opcode = ord($data[0]) & 31;
            if ($opcode == 9) { //ping
                $this->pong();
            } else {
                $data = $this->getPayload();
                $data = json_decode($data, 1);
                $data['event']['text'] = $this->dataProcessing($data['event']['text']);
                $anon($data);
            }
        }
    }

    private function dataProcessing($data) {
        $data = str_replace("\u003cbr\u003e", "\n", $data);
        return html_entity_decode($data, ENT_QUOTES, 'UTF-8');
    }

    private function read($length) {
        $data = '';
        while (strlen($data) < $length) {
            $buffer = fread($this->socket, $length - strlen($data));
            $data .= $buffer;
        }
        return $data;
    }

    private function pong() {
        $payload = 'PONG';
        $frameHead = [];
        $payloadLength = strlen($payload);
        $frameHead[0] = 138;
        $frameHead[1] = $payloadLength + 128;
        foreach (array_keys($frameHead) as $i) {
            $frameHead[$i] = chr($frameHead[$i]);
        }
        $mask = [];
        for ($i = 0; $i < 4; $i++) {
            $mask[$i] = chr(rand(0, 255));
        }
        $frameHead = array_merge($frameHead, $mask);
        $frame = implode('', $frameHead);

        for ($i = 0; $i < $payloadLength; $i++) {
            $frame .= $payload[$i] ^ $mask[$i % 4];
        }

        fwrite($this->socket, $frame);
    }

    private function getPayload() {
        $payload_length = '';
        $data = $this->read(2);
        for ($i = 0; $i < strlen($data); $i++) $payload_length .= sprintf("%08b", ord($data[$i]));
        $payload_length = bindec($payload_length);
        $payload = $this->read($payload_length);
        return $payload;
    }

    private function request($url, $type, $json = []) {
        try {
            return $this->request_core($url, $type, $json);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }

    private function request_core($url, $type, $json) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($json) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type:application/json"
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = json_decode(curl_exec($ch), True);
        curl_close($ch);
        if (empty($result)) {
            throw new SimpleVkException(77777, 'Вк вернул пустой ответ');
        }
        if ($result['code'] == 400) {
            throw new SimpleVkException($result['code'], json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        return $result;
    }

    private function connect() {
        $context = stream_context_create();
        $this->socket = @stream_socket_client($this->stream_url, $errno, $errstr, 1000, STREAM_CLIENT_CONNECT, $context);
        $key = $this->generateKey();
        $header = "GET {$this->stream_query} HTTP/1.1\r\n" .
            "host: streaming.vk.com:443\r\n" .
            "user-agent:websocket-client-php\r\n" .
            "connection:Upgrade\r\n" .
            "upgrade:websocket\r\n" .
            "sec-websocket-key: $key\r\n" .
            "sec-websocket-version:13\r\n\r\n";
        fwrite($this->socket, $header);
        $response = stream_get_line($this->socket, 1024, "\r\n\r\n");
    }

    private function generateKey() {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"$&/()=[]{}0123456789';
        $key = '';
        $chars_length = strlen($chars);
        for ($i = 0; $i < 16; $i++) $key .= $chars[mt_rand(0, $chars_length - 1)];
        return base64_encode($key);
    }
}