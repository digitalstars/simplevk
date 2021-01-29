<?php

namespace DigitalStars\SimpleVK;

class Message extends BaseConstructor {
    use FileUploader;
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
        return $this->config['voice'] ?? null;
    }

    public function load($cfg = []) {
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
        return $this->config['kbd'] ?? null;
    }

    public function a_run($id) {
        $this->checkBot();
        $this->config['func_after_chain'][] = ['f' => 'run', 'args' => $id];
        return $this;
    }

    public function b_run($id) {
        $this->checkBot();
        $this->config['func_before_chain'][] = ['f' => 'run', 'args' => $id];
        return $this;
    }

    public function run() {
        $id = $this->generateNewAction();
        $this->config['func_after_chain'][] = ['f' => 'run', 'args' => $id];
        return $this->bot->cmd($id);
    }

    public function edit($is_save = true, $save_params = ['text', 'img', 'doc', 'attachments', 'params', 'voice', 'kbd']) {
        if (!empty(array_intersect(array_keys($this->config), ['text', 'img', 'doc', 'attachments', 'params', 'voice', 'kbd', 'func']))) {
            $id = $this->generateNewAction();
            $this->config['func_after_chain'][] = ['f' => 'edit', 'args' => $id];
            if ($is_save) {
                $new_msg_config = [];
                foreach ($this->config as $key => $val)
                    if (in_array($key, $save_params))
                        $new_msg_config[$key] = $val;
                return $this->bot->cmd($id)->load($new_msg_config);
            } else
                return $this->bot->cmd($id);
        } else {
            $this->config['is_edit'] = true;
            return $this;
        }
    }

    private function generateNewAction() {
        $this->checkBot();
        $id = explode('$', $this->id_action);
        if (count($id) > 2 or (isset($id[1]) and !is_numeric($id[1])))
            throw new SimpleVkException(0, "Нельзя использовать '$' в id действий");
        $id[1] = isset($id[1]) ? ($id[1] + 1) : 1;
        return join('$', $id);
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

    public function forward($message_ids = null, $conversation_message_ids = null, $peer_id = null, $owner_id = null) {
        if ($message_ids == null && $conversation_message_ids == null) {
            $this->config['forward'] = ['forward' => []];
            return $this;
        }

        $ids = $message_ids ?: $conversation_message_ids;
        $forward_messages = (is_array($ids)) ? join(',', $ids) : $ids;
        if ($conversation_message_ids == null && $peer_id == null && $owner_id == null)
            $this->config['forward'] = ['forward_messages' => $forward_messages];
        else {
            $this->config['forward'] = ['forward' => [
                ($message_ids ? 'message_ids' : 'conversation_message_ids') => $forward_messages,
                'peer_id' => $peer_id]];
            if ($owner_id)
                $this->config['forward']['forward']['owner_id'] = $owner_id;
        }
        return $this;
    }

    public function getForward() {
        return $this->config['forward']['forward_messages'] ?? $this->config['forward']['forward'] ?? null;
    }

    public function clearForward() {
        $this->config['forward'] = [];
        return $this;
    }

    public function reply($message_id = null, $conversation_message_id = null) {
        if ($message_id == null && $conversation_message_id == null) {
            $this->config['forward'] = ['forward' => ['is_reply' => true]];
            return $this;
        }
        $this->config['forward'] = ['forward' => [
            ($message_id ? 'message_ids' : 'conversation_message_ids') => $message_id ?: $conversation_message_id,
            'is_reply' => true]];
        return $this;
    }

//    public function for

    public function eventAnswerSnackbar($text) {
        $this->checkBot();
        $this->config['event'] = [
            'type' => 0,
            'text' => $text
        ];
        return $this;
    }

    public function eventAnswerOpenLink($url) {
        $this->checkBot();
        $this->config['event'] = [
            'type' => 1,
            'url' => $url
        ];
        return $this;
    }

    public function eventAnswerOpenApp($app_id, $owner_id = null, $hash = null) {
        $this->checkBot();
        $this->config['event'] = [
            'type' => 2,
            'app_id' => $app_id,
            'owner_id' => $owner_id,
            'hash' => $hash
        ];
        return $this;
    }

    public function redirect($id) {
        $this->checkBot();
        echo "$this->id_action, $id\n";
        return $this->bot->redirect($this->id_action, $id);
    }

    private function checkBot() {
        if (is_null($this->bot))
            throw new SimpleVkException(0, "Метод только для событий конструктора ботов");
    }

    public function carousel() {
        $config = [];
        $this->config['carousel'][] = &$config;
        return Carousel::create($config, $this);
    }

    public function setCarousel($carousel) {
        if ($carousel instanceof Carousel)
            $carousel = [$carousel];
        foreach ($carousel as $element)
            $this->config['carousel'][] = $element->dump();
        return $this;
    }

    public function clearCarousel() {
        $this->config['carousel'] = [];
        return $this;
    }

    public function uploadAllImages() {
        $images = [];
        foreach ($this->config['img'] ?? [] as $img_path) {
            $img_path = $img_path[0];
            $images[] = $this->getMsgAttachmentUploadImage(0, $img_path);
        }
        $this->config['img'] = [];
        $this->config['attachments'] = array_merge($this->config['attachments'], $images);
        return $this;
    }

    private function parseKbd($kbd) {
        $kbd_result = $kbd;
        foreach ($kbd as $row_index => $row)
            foreach ($row as $col_index => $col) {
                if (!is_string($col)) {
                    $kbd_result[$row_index][$col_index] = $col;
                    continue;
                }
                if (!isset($this->buttons[$col]))
                    throw new SimpleVkException(0, "Кнопки с id " . $col . " не найдена. Возможно вы используете для отправки сообщения не тот экземпляр класса, в котором была создана эта кнопка.");
                $btn = $this->buttons[$col];
                $payload = ['name' => $col];
                if (is_array($btn[1]))
                    $btn[1] = array_merge($btn[1], $payload);
                else
                    $btn[1] = $payload;
                $kbd_result[$row_index][$col_index] = $btn;
            }
        return $kbd_result;
    }

    private function parseKeyboard($keyboard_raw = []) {
        $keyboard = [];
        foreach ($keyboard_raw as $row => $button_str) {
            foreach ($button_str as $col => $button) {
                $keyboard[$row][$col]['action']['type'] = $button[0];
                if ($button[1] != null)
                    $keyboard[$row][$col]['action']['payload'] = json_encode($button[1], JSON_UNESCAPED_UNICODE);
                switch ($button[0]) {
                    case 'callback':
                    case 'text':
                    {
                        $keyboard[$row][$col]['color'] = $button[3];
                        $keyboard[$row][$col]['action']['label'] = $button[2];
                        break;
                    }
                    case 'vkpay':
                    {
                        $keyboard[$row][$col]['action']['hash'] = "action={$button[2]}";
                        $keyboard[$row][$col]['action']['hash'] .= ($button[3] < 0) ? "&group_id=" . $button[3] * -1 : "&user_id={$button[3]}";
                        $keyboard[$row][$col]['action']['hash'] .= (isset($button[4])) ? "&amount={$button[4]}" : '';
                        $keyboard[$row][$col]['action']['hash'] .= (isset($button[5])) ? "&description={$button[5]}" : '';
                        $keyboard[$row][$col]['action']['hash'] .= (isset($button[6])) ? "&data={$button[6]}" : '';
                        $keyboard[$row][$col]['action']['hash'] .= '&aid=1';
                        break;
                    }
                    case 'open_app':
                    {
                        $keyboard[$row][$col]['action']['label'] = $button[2];
                        $keyboard[$row][$col]['action']['app_id'] = $button[3];
                        if (isset($button[4]))
                            $keyboard[$row][$col]['action']['owner_id'] = $button[4];
                        if (isset($button[5]))
                            $keyboard[$row][$col]['action']['hash'] = $button[5];
                        break;
                    }
                    case 'open_link':
                    {
                        $keyboard[$row][$col]['action']['link'] = $button[2];
                        $keyboard[$row][$col]['action']['label'] = $button[3];
                    }
                }
            }
        }
        return $keyboard;
    }

    private function generateCarousel($carousels, $id) {
        if (!is_array($carousels))
            $carousels = [$carousels];
        $template = ["type" => 'carousel', 'elements' => []];
        foreach ($carousels as $carousel) {
            if ($carousel instanceof Carousel)
                $carousel = $carousel->dump();
            $element['action'] = $carousel['action'];
            if (isset($carousel['kbd']))
                $element['buttons'] = $this->parseKeyboard([$carousel['kbd']])[0];
            if (isset($carousel['title']))
                $element['title'] = $carousel['title'];
            if (isset($carousel['description']))
                $element['description'] = $carousel['description'];
            if (isset($carousel['img']))
                $element['photo_id'] = str_replace('photo', '', $this->getMsgAttachmentUploadImage($id, $carousel['img']));
            $template['elements'][] = $element;
        }
        return json_encode($template, JSON_UNESCAPED_UNICODE);
    }

    private function assembleMsg($id, $var) {
        $this->config_cache = $this->config;

        if ($this->preProcessing($var))
            return null;

        $attachments = [];
        if (!empty($this->config['img']))
            foreach ($this->config['img'] as $img)
                $attachments[] = $this->getMsgAttachmentUploadImage($id, $img[0]);
        if (!empty($this->config['doc']))
            foreach ($this->config['doc'] as $doc)
                $attachments[] = $this->getMsgAttachmentUploadDoc($id, $doc[0], $doc[1]);
        if (!empty($this->config['voice']))
            $attachments[] = $this->getMsgAttachmentUploadVoice($id, $this->config['voice']);
        if (!empty($this->config['attachments']))
            $attachments = array_merge($attachments, $this->config['attachments']);
        if (!empty($this->config['params']['attachment'])) {
            $attachments = array_merge($attachments, $this->config['params']['attachment']);
            unset($this->config['params']['attachment']);
        }
        $attachments = !empty($attachments) ? ['attachment' => join(",", $attachments)] : [];

        if (!empty($this->config['carousel'])) {
            $carousels = $this->config['carousel'];
            foreach ($carousels as $key => $carousel)
                if (!empty($carousel['kbd']))
                    $carousels[$key]['kbd'] = $this->parseKbd([$carousel['kbd']])[0];
            $template = ['template' => $this->generateCarousel($carousels, $id)];
        } else
            $template = [];

        if (isset($this->config['kbd']['kbd']))
            $kbd = ['keyboard' => json_encode([
                'one_time' => $this->config['kbd']['one_time'],
                'buttons' => $this->parseKeyboard($this->parseKbd($this->config['kbd']['kbd'])),
                'inline' => $this->config['kbd']['inline']
            ], JSON_UNESCAPED_UNICODE)];
        else
            $kbd = [];

        $params = $this->config['params'] ?? [];

        if (isset($this->config['forward']['forward'])) {
            $forward = $this->config['forward']['forward'];
            if (empty($forward['peer_id'])) {
                $this->vk->initPeerID($init_peer_id);
                $forward['peer_id'] = $init_peer_id;
            }
            if (empty($forward['message_ids']) && empty($forward['conversation_message_ids'])) {
                $this->vk->initMsgID($msg_id)->initConversationMsgID($convers_msg_id);
                $forward[$msg_id ? 'message_ids' : 'conversation_message_ids'] = $msg_id ?: $convers_msg_id;
            }
            $forward = ['forward' => json_encode($forward)];
        } else
            $forward = $this->config['forward'] ?? [];

        $text = !empty($this->config['text']) ? ['message' => $this->config['text']] : [];
        return $text + $params + $attachments + $kbd + $template + $forward;
    }

    public function sendEdit($peer_id, $message_id = null, $conversation_message_id = null, $var = null) {
        if (is_null($message_id) and is_null($conversation_message_id))
            throw new SimpleVkException(0, "Нужно указать хотя-бы какой то из message_id");
        $query = $this->assembleMsg($peer_id, $var);

        if (empty($query))
            $result = null;
        else {
            $message_id_key = is_null($message_id) ? 'conversation_message_id' : 'message_id';
            $message_id = $message_id ?? $conversation_message_id;
            $result = $this->request('messages.edit', ['peer_id' => $peer_id, $message_id_key => $message_id] + $query);
        }
        $this->postProcessing($peer_id, $result, $var);
        return $result;
    }

    public function send($id = null, $vk = null, $var = null) {
        if (empty($this->vk) and isset($vk))
            $this->vk = $vk;
        if (empty($this->vk))
            throw new SimpleVkException(0, "Экземпляр SimpleVK не передан");
        if (!empty($this->config['real_id']))
            $id = $this->config['real_id'];
        if (empty($id))
            $this->vk->initPeerID($id);

        $query = $this->assembleMsg($id, $var);
        if (is_null($query))
            return null;
        if (empty($query))
            $result = null;
        else
            $result = $this->request('messages.send', ['peer_id' => $id] + $query);
        $this->postProcessing($id, $result, $var);
        return $result;
    }
}