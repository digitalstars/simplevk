<?php


namespace DigitalStars\simplevk;


class Message extends BaseConstructor {
    private $buttons;
    /** @var Bot */
    protected $bot = null;
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

    public function getVoice() {
        return $this->config['voice'];
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
        if (empty($this->vk) and isset($vk))
            $this->vk = $vk;
        if (empty($this->vk))
            throw new SimpleVkException(0, "Экземпляр SimpleVK не передан");
        if (empty($id))
            $this->vk->initVars($id);
        $this->config_cache = $this->config;
        $attachments = $this->preProcessing($id, $var);
        if (is_bool($attachments))
            return null;
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
        $this->postProcessing($result, $var);
        return $result;
    }
}