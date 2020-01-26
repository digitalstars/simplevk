<?php

namespace DigitalStars\simplevk;

class Streaming {
    private $rules_url;
    private $stream_url;
    private $token;
    private $version;

    public function __construct($token, $version) {
        $this->token = $token;
        $this->version = $version;
        $this->updateStreamingServer();
    }

    private function updateStreamingServer() {
        $response = $this->request("https://api.vk.com/method/streaming.getServerUrl?v=$this->version&access_token=$this->token", 'GET')['response'];
        print $response[key];
        $this->rules_url = "https://$response[endpoint]/rules?key=$response[key]";
        $this->stream_url = "ssl://$response[endpoint]:4444/stream?key=$response[key]";
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
        $context = stream_context_create([
            'ssl' => [
                'verify_peer_name' => false,
                'verify_peer' => false
            ]
        ]);
        $socket = stream_socket_client($this->stream_url, $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);

        $header = "GET / HTTP/1.1\r\n" .
            "User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0\r\n".
            "Host: www.ssllabs.com\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Version: 13\r\n\r\n";

        fwrite($socket, $header);

        while (!feof($socket)) {
            $context = fgets($socket, 1024);
            print_r($context);
            $anon($context);
        }

        fclose($socket);
    }

    private function request($url, $type, $json = []) {
        if (function_exists('curl_init')) {
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
            if($url == $this->stream_url) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Content-Type:application/json",
                    "Connection: upgrade",
                    "Upgrade: websocket",
                    "Sec-Websocket-Version: 13"
                ]);
            }
            curl_setopt($ch, CURLOPT_URL, $url);
            $result = json_decode(curl_exec($ch), True);
            curl_close($ch);
            return $result;
        }
    }
}