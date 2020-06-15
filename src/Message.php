<?php


namespace DigitalStars\simplevk;


class Message extends BaseConstructor {
    use FileUploader;

    public function __construct($vk = null, &$cfg = null) {
        parent::__construct($vk, $cfg);
    }

    public static function create($vk = null, &$cfg = null) {
        return new self($vk, $cfg);
    }

    public function voice($path) {
        $this->config['voice'] = $path;
        return $this;
    }

    public function kbd($kbd) {
        $this->config['kbd'] = $kbd;
        return $this;
    }

    public function getKbd() {
        return $this->config['kbd'];
    }

    public function send($id = null, $vk = null, $buttons = null) {
        if (empty($this->vk))
            $this->vk = $vk;
        if (empty($id))
            $this->vk->initVars($id);
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
        if (isset($buttons) and isset($this->config['kbd']))
            foreach ($this->config['kbd'] as $row_index => $row)
                foreach ($row as $col_index => $col) {
                    $btn = $buttons[$col];
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
        return $this->request('messages.send', ['peer_id' => $id] + $text + $params + $attachments + $kbd);
    }
}