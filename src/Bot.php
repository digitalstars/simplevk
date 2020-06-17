<?php


namespace DigitalStars\simplevk;

class Bot {
    use FileUploader;
    private $vk = null;
    private $config = [];
    private $is_text_start = false;

    public function __construct($vk) {
        $this->vk = $vk;
    }

    public static function create($vk) {
        return new self($vk);
    }

    public function isText($start) {
        $this->is_text_start = $start;
        return $this;
    }

    private function newAction($id) {
        if (!isset($this->config['action'][$id]))
            $this->config['action'][$id] = [];
        return new Message($this->vk, $this->config['action'][$id], $this->config['btn']);
    }

    public function btn($id, $btn = null, $is_text_triggered = false) {
        if (isset($btn))
            $this->config['btn'][$id] = $btn;
        if ($is_text_triggered)
            $this->cmd($id, $btn[2]);
        return $this->newAction($id);
    }

    public function __call($name, $arguments) {
        if (empty($arguments))
            return $this->btn($name);
        if (is_array($arguments[0]))
            return $this->btn($name, $arguments[0]);
        return $this->btn($name, $this->vk->buttonText($arguments[0], $arguments[1] ?? 'white'));
    }

    public function cmd($id, $mask = null, $is_case = false) {
        if (!$is_case)
            $mask = mb_strtolower($mask);
        if (isset($mask))
            $this->config['mask'][$id] = [$mask, $is_case];
        return $this->newAction($id);
    }

    public function preg_cmd($id, $mask = null) {
        if (isset($mask))
            $this->config['preg_mask'][$id] = $mask;
        return $this->newAction($id);
    }

    public function dump() {
        return $this->config;
    }

    public function redirect($id, $to_id) {
        $this->config['action'][$id] = &$this->config['action'][$to_id];
        return $this;
    }

    public function run($send = null, $id = null) {
        $this->vk->initVars($id_now, $message, $payload, $user_id, $type);
        $id = $id ?? $id_now;
        if (isset($send) and isset($this->config['action'][$send]))
            return Message::create($this->vk, $this->config['action'][$send], $this->config['btn'])->send($id);
        if ($type != 'message_new')
            return null;
        if (isset($payload['name']) and isset($this->config['action'][$payload['name']]))
            return Message::create($this->vk, $this->config['action'][$payload['name']], $this->config['btn'])->send($id);
        if ((isset($payload['command']) and $payload['command'] == 'start') or $this->is_text_start)
            return Message::create($this->vk, $this->config['action']['first'], $this->config['btn'])->send($id);
        if (!empty($message)) {
            if (isset($this->config['mask'])) {
                $arr_msg = explode(' ', $message);
                foreach ($this->config['mask'] as $action => $mask) {
                    $mask_words = explode(' ', $mask[0]);
                    if (count($mask_words) != count($arr_msg))
                        continue;
                    $flag = true;
                    $result_parse = [];
                    foreach ($mask_words as $index => $word) {
                        if ($word == '%n') {
                            $number_temp = str_replace(",", '.', $arr_msg[$index]);
                            if (is_numeric($number_temp))
                                $result_parse[] = (double)$number_temp;
                        } else if ($word == '%s' and is_string($arr_msg[$index])) {
                            $result_parse[] = $arr_msg[$index];
                        } else if ((!$mask[1] or $word != $arr_msg[$index]) and ($mask[1] or $word != mb_strtolower($arr_msg[$index]))) {
                            $flag = false;
                            break;
                        }
                    }
                    if ($flag)
                        return Message::create($this->vk, $this->config['action'][$action], $this->config['btn'])->send($id, null, $result_parse);
                }
            }
            if (isset($this->config['preg_mask']))
                foreach ($this->config['preg_mask'] as $action => $preg_mask)
                    if (preg_match($preg_mask, $message, $result_parse))
                        return Message::create($this->vk, $this->config['action'][$action], $this->config['btn'])->send($id, null, $result_parse);
        }
        if (isset($this->config['action']['other']))
            return Message::create($this->vk, $this->config['action']['other'], $this->config['btn'])->send($id);
        return null;
    }
}