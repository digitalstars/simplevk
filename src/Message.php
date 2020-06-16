<?php


namespace DigitalStars\simplevk;


class Message extends BaseConstructor {
    use FileUploader;

    private $buttons;

    public function __construct($vk = null, &$cfg = null, &$buttons = null) {
        $this->buttons = &$buttons;
        parent::__construct($vk, $cfg);
    }

    public static function create($vk = null, &$cfg = null, &$buttons = null) {
        return new self($vk, $cfg, $buttons);
    }

    public function voice($path) {
        $this->config['voice'] = $path;
        return $this;
    }

    public function load($cfg) {
        if ($cfg instanceof Message) {
            $this->vk = $cfg->vk;
            $this->config = $cfg->config;
            $this->buttons = &$cfg->buttons;
        } else
            $this->config = $cfg;
        return $this;
    }

    public function kbd($kbd = []) {
        $this->config['kbd'] = $kbd;
        return $this;
    }

    public function getKbd() {
        return $this->config['kbd'];
    }

    public function send($id = null, $vk = null, $var = null) {
        if (empty($this->vk))
            $this->vk = $vk;
        if (empty($id))
            $this->vk->initVars($id);
        $cfg_cache = $this->config;
        if (isset($this->config['func']) and is_callable($this->config['func']))
            if ($this->config['func']($this, $id, $var))
                return null;
        $attachments = [];
        if (isset($this->config['img']))
            foreach ($this->config['img'] as $img)
                $attachments[] = $this->uploadImage($id, $img[0]);
        if (isset($this->config['doc']))
            foreach ($this->config['doc'] as $doc)
                $attachments[] = $this->uploadDocsMessages($id, $doc[0], $doc[1]);
        if (isset($this->config['voice']))
            $attachments[] = $this->uploadVoice($id, $this->config['voice']);
        $attachments = ['attachment' => join(",", $attachments)];
        if (isset($this->buttons) and isset($this->config['kbd']))
            foreach ($this->config['kbd'] as $row_index => $row)
                foreach ($row as $col_index => $col) {
                    if (!is_string($col)) {
                        $kbd[$row_index][$col_index] = $col;
                        continue;
                    }
                    $btn = $this->buttons[$col];
                    $payload = ['name' => $col];
                    if (is_array($btn[1]))
                        $btn[1] = array_merge($btn[1], $payload);
                    else
                        $btn[1] = $payload;
                    $kbd[$row_index][$col_index] = $btn;
                }
        $kbd = isset($kbd) ? ['keyboard' => $this->vk->generateKeyboard($kbd)]
            : (isset($this->config['kbd']) ? ['keyboard' => $this->vk->generateKeyboard($this->config['kbd'])]
                : []);
        $params = $this->config['params'] ?? [];
        $text = isset($this->config['text']) ? ['message' => $this->config['text']] : [];
        $query = $text + $params + $attachments + $kbd;
        if (empty($query))
            return null;
        $result = $this->request('messages.send', ['peer_id' => $id] + $query);
        if (isset($this->config['func_after']) and is_callable($this->config['func_after']))
            $this->config['func_after']($result, $var);
        $this->config = $cfg_cache;
        return $result;
    }
}