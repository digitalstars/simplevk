<?php


namespace DigitalStars\simplevk;

class Bot {
    use FileUploader;
    private $vk = null;
    private $config = [];

    public function __construct($vk) {
        $this->vk = $vk;
    }

    public static function create($vk) {
        return new self($vk);
    }

    public function btn($id, $btn = null) {
        if (isset($btn))
            $this->config['btn'][$id] = $btn;
        $this->config['action'][$id] = [];
        return new Message($this->vk, $this->config['action'][$id]);
    }

    public function dump() {
        return $this->config;
    }

    public function run($start = false) {
        $this->vk->initVars($id, $message, $payload, $user_id, $type);
        if ($type != 'message_new')
            return null;
        if (isset($payload['name']) and isset($this->config['action'][$payload['name']]))
            return Message::create($this->vk, $this->config['action'][$payload['name']])->send($id, null, $this->config['btn']);
        if (mb_strtolower($message) == '!старт' or (isset($payload['command']) and $payload['command'] == 'start') or $start)
            return Message::create($this->vk, $this->config['action']['first'])->send($id, null, $this->config['btn']);
    }
}