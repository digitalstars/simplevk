<?php


namespace DigitalStars\simplevk;


class Message extends BaseConstructor {
    use FileUploader;

    private $buttons;
    /** @var Bot */
    private $bot = null;
    private $id_action = null;

    public function __construct($vk = null, &$cfg = null, $bot = null, &$buttons = null, $id_action = null) {
        $this->buttons = &$buttons;
        $this->bot = $bot;
        $this->id_action = $id_action;
        parent::__construct($vk, $cfg);
    }

    public static function create($vk = null, &$cfg = null, $bot = null, &$buttons = null, $id_action = null) {
        return new self($vk, $cfg, $bot, $buttons, $id_action);
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

    public function kbd($kbd = [], $inline = false, $one_time = False) {
        if (is_string($kbd) or (isset($kbd[0]) and is_string($kbd[0])))
            $kbd = [[$kbd]];
        $this->config['kbd'] = ['kbd' => $kbd, 'inline' => $inline, 'one_time' => $one_time];
        return $this;
    }

    public function getKbd() {
        return $this->config['kbd'];
    }

    public function a_run($id) {
        if (is_null($this->bot))
            throw new SimpleVkException(0, "Метод только для событий конструктора ботов");
        $this->config['func_after_chain'][] = ['f' => 'run', 'args' => $id];
        return $this;
    }

    public function b_run($id) {
        if (is_null($this->bot))
            throw new SimpleVkException(0, "Метод только для событий конструктора ботов");
        $this->config['func_before_chain'][] = ['f' => 'run', 'args' => $id];
        return $this;
    }

    public function run() {
        if (is_null($this->bot))
            throw new SimpleVkException(0, "Метод только для событий конструктора ботов");
        $id = explode('$', $this->id_action);
        if (count($id) > 2 or (isset($id[1]) and !is_numeric($id[1])))
            throw new SimpleVkException(0, "Нельзя использовать '$' в id действий");
        $id[1] = isset($id[1]) ? ($id[1] + 1) : 1;
        $id = join('$', $id);
        $this->config['func_after_chain'][] = ['f' => 'run', 'args' => $id];
        return $this->bot->cmd($id);
    }

    public function access() {
        if (!is_null($this->bot))
            $this->bot->access($this->id_action, func_get_args());
        return $this;
    }

    public function getAccess() {
        if (!is_null($this->bot))
            return $this->bot->getAccess($this->id_action);
        return null;
    }

    public function notAccess() {
        if (!is_null($this->bot))
            $this->bot->notAccess($this->id_action, func_get_args());
        return $this;
    }

    public function getNotAccess() {
        if (!is_null($this->bot))
            return $this->bot->getNotAccess($this->id_action);
        return null;
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
        if (!empty($this->config['func_before_chain']))
            foreach ($this->config['func_before_chain'] as $func) {
                if ($func['f'] == 'run')
                    $this->bot->run($func['args']);
                else
                    call_user_func_array($func['f'], $func['args']);
                if ($this->bot->getStatus())
                    return null;
            }
        $attachments = [];
        if (isset($this->config['img']))
            foreach ($this->config['img'] as $img)
                $attachments[] = $this->uploadImage($id, $img[0]);
        if (isset($this->config['doc']))
            foreach ($this->config['doc'] as $doc)
                $attachments[] = $this->uploadDocsMessages($id, $doc[0], $doc[1]);
        if (isset($this->config['voice']))
            $attachments[] = $this->uploadVoice($id, $this->config['voice']);
        if (isset($this->config['attachments']))
            $attachments = array_merge($attachments, $this->config['attachments']);
        if (isset($this->config['params']['attachment'])) {
            $attachments = array_merge($attachments, $this->config['params']['attachment']);
            unset($this->config['params']['attachment']);
        }
        $attachments = !empty($attachments) ? ['attachment' => join(",", $attachments)] : [];
        if (isset($this->buttons) and isset($this->config['kbd']))
            foreach ($this->config['kbd']['kbd'] as $row_index => $row)
                foreach ($row as $col_index => $col) {
                    if (!is_string($col)) {
                        $kbd[$row_index][$col_index] = $col;
                        continue;
                    }
                    if (!isset($this->buttons[$col]))
                        throw new SimpleVkException(0, "Кнопки с id ".$col." не существует");
                    $btn = $this->buttons[$col];
                    $payload = ['name' => $col];
                    if (is_array($btn[1]))
                        $btn[1] = array_merge($btn[1], $payload);
                    else
                        $btn[1] = $payload;
                    $kbd[$row_index][$col_index] = $btn;
                }
        $kbd = isset($kbd) ? ['keyboard' => $this->vk->generateKeyboard($kbd, $this->config['kbd']['inline'], $this->config['kbd']['one_time'])]
            : (isset($this->config['kbd']) ? ['keyboard' => $this->vk->generateKeyboard($this->config['kbd']['kbd'], $this->config['kbd']['inline'], $this->config['kbd']['one_time'])]
                : []);
        $params = $this->config['params'] ?? [];
        $text = isset($this->config['text']) ? ['message' => $this->config['text']] : [];
        $query = $text + $params + $attachments + $kbd;
        if (empty($query))
            $result = null;
        else
            $result = $this->request('messages.send', ['peer_id' => $id] + $query);
        if (isset($this->config['func_after']) and is_callable($this->config['func_after']))
            if($this->config['func_after']($result, $var))
                return $result;
        if (!empty($this->config['func_after_chain']))
            foreach ($this->config['func_after_chain'] as $func) {
                if ($func['f'] == 'run')
                    $this->bot->run($func['args']);
                else
                    call_user_func_array($func['f'], $func['args']);
                if ($this->bot->getStatus())
                    return $result;
            }
        $this->config = $cfg_cache;
        return $result;
    }
}