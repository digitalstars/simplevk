<?php

namespace DigitalStars\SimpleVK;

class Bot {
    use FileUploader;
    /** @var SimpleVK */
    private $vk = null;
    private $config = [];
    private $is_text_start = false;
    private $is_text_button_triggered = false;
    private $is_all_btn_callback = false;
    private $case_default = false;
    private $status = 1;
    private $color = 'white';
    private $compile_files = [];
    private $events = ['message_new', 'message_event'];

    public function __construct($token_or_vk, $version = null, $also_version = null) {
        if ($token_or_vk instanceof SimpleVK) {
            $this->vk = $token_or_vk;
        } else {
            if (is_null($version))
                throw new SimpleVkException(0, 'При передачи токена, необходимо передать и версию апи');
            $this->vk = new SimpleVK($token_or_vk, $version, $also_version);
        }
    }

    public static function create($token_or_vk, $version = null, $also_version = null) {
        return new self($token_or_vk, $version, $also_version);
    }

    public function vk() {
        return $this->vk;
    }

    public function setConfirm($str) {
        $this->vk->setConfirm($str);
        return $this;
    }

    public function setSecret($str) {
        $this->vk->setSecret($str);
        return $this;
    }

    public function getEvents() {
        return $this->events;
    }

    public function addEvents($events) {
        if (is_array($events))
            $this->events = array_merge($this->events, $events);
        else
            $this->events[] = $events;
        return $this;
    }

    public function events($events) {
        if (is_array($events))
            $this->events = $events;
        else
            $this->events = [$events];
        return $this;
    }

    public function isStartTextTriggered($start) {
        $this->is_text_start = $start;
        return $this;
    }

    public function isAllBtnCallback($is = true) {
        $this->is_all_btn_callback = $is;
        return $this;
    }

    private function newAction($id) {
        if (!isset($this->config['action'][$id]))
            $this->config['action'][$id] = [];
        return new Message($this->vk, $this->config['action'][$id], $this, $this->config['btn'], $id);
    }

    public function break() {
        $this->status = 1;
    }

    public function getStatus() {
        return $this->status;
    }

    public function btn($id, $btn = null, $is_callback = false, $is_text_triggered = false) {
        $is_callback = (!$this->is_all_btn_callback && $is_callback) || ($this->is_all_btn_callback && !$is_callback);
        if (isset($btn)) {
            if (is_array($btn)) {
                if (!isset($btn[1]))
                    $btn[1] = $this->color;
                if (count($btn) == 2 and in_array($btn[1], ['white', 'green', 'red', 'blue']))
                    $this->config['btn'][$id] = $is_callback ? $this->vk->buttonCallback($btn[0], $btn[1]) : $this->vk->buttonText($btn[0], $btn[1]);
                else
                    $this->config['btn'][$id] = $btn;
            } else
                $this->config['btn'][$id] = $is_callback ? $this->vk->buttonCallback($btn, $this->color) : $this->vk->buttonText($btn, $this->color);
            if ($this->config['btn'][$id][0] == 'text' and ($is_text_triggered or $this->is_text_button_triggered))
                $this->cmd($id, $this->config['btn'][$id][2]);
        }
        return $this->newAction($id);
    }

    public function __call($name, $arguments) {
        return $this->btn($name, $arguments[0] ?? null, $arguments[1] ?? null);
    }

    public function cmd($id, $mask = null, $is_case = null) {
        $is_case = $is_case ?? $this->case_default;
        if (isset($mask)) {
            $this->config['mask'][$id] = [[], $is_case];
            if (is_array($mask))
                foreach ($mask as $m) {
                    if (!$is_case)
                        $m = mb_strtolower($m);
                    $this->config['mask'][$id][0][] = $m;
                }
            else {
                if (!$is_case)
                    $mask = mb_strtolower($mask);
                $this->config['mask'][$id][0][] = $mask;
            }
        }
        return $this->newAction($id);
    }

    public function access($id, $access) {
        $this->config['action'][$id]['access'] = $access;
        return $this;
    }

    public function getAccess($id) {
        return $this->config['action'][$id]['access'] ?? null;
    }

    public function notAccess($id, $access) {
        $this->config['action'][$id]['not_access'] = $access;
        return $this;
    }

    public function getNotAccess($id) {
        return $this->config['action'][$id]['not_access'] ?? null;
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

    private function out_array($array, $var, $_livel = null, $stack = []) {
        $out = $margin = '';
        $nr = "\n";
        $tab = "\t";

        if (is_null($_livel)) {
            $out .= '$' . $var . ' = ';
            if (!empty($array)) {
                $out .= $this->out_array($array, $var, 0);
            }
            $out .= ';';
        } else {
            for ($n = 1; $n <= $_livel; $n++) {
                $margin .= $tab;
            }
            $_livel++;
            if (is_array($array)) {
                $i = 1;
                $count = count($array);
                $out .= '[' . $nr;
                foreach ($array as $key => $row) {
                    $out .= $margin . $tab;
                    if (is_numeric($key)) {
                        $out .= $key . ' => ';
                    } else {
                        $out .= "'" . $key . "' => ";
                    }

                    if (is_array($row)) {
                        $stack[] = $key;
                        $out .= $this->out_array($row, $var, $_livel, $stack);
                        array_pop($stack);
                    } elseif (is_null($row)) {
                        $out .= 'null';
                    } elseif (is_numeric($row)) {
                        $out .= $row;
                    } elseif (is_bool($row)) {
                        $out .= ($row) ? 'true' : 'false';
                    } elseif ($stack[0] == 'action' and ($key === 'func' or $key === 'func_after') and is_callable($row)) {
                        $out .= $this->getFunction(end($stack), $key);
                    } else {
                        $out .= "'" . addslashes($row) . "'";
                    }
                    if ($count > $i) {
                        $out .= ',';
                    }
                    $out .= $nr;
                    $i++;
                }
                $out .= $margin . ']';
            } else {
                $out .= "'" . addslashes($array) . "'";
            }
        }
        return $out;
    }

    public function compile($var = 'compile', $file = 'cache', $is_write = true) {
        $this->compile_files = [];
        $source = $this->out_array($this->config, $var);

        $namespaces_all = [];
        foreach ($this->compile_files as $filename) {
            $tokens = token_get_all(file_get_contents($filename));
            $flag = 0;
            $namespaces = '';
            foreach ($tokens as $token) {
                if (is_array($token))
                    if (!$flag)
                        if ($token[0] == T_USE)
                            $flag = 1;
                        else if ($token[0] !== T_WHITESPACE and $token[0] !== T_OPEN_TAG)
                            $flag = 2;
                        else if ($token[0] == T_COMMENT or $token[0] == T_DOC_COMMENT or $token[0] == T_INLINE_HTML)
                            continue;
                $var = is_array($token) ? $token[1] : $token;
                if ($flag == 1) {
                    $namespaces .= $var;
                    if ($var == ';') {
                        if (!in_array($namespaces, $namespaces_all))
                            $namespaces_all[] = $namespaces;
                        $namespaces = '';
                        $flag = 0;
                    }
                } else if ($flag == 2 and $var == ';') {
                    $flag = 0;
                }
            }
        }
        $check_arr_namespace = [];
        foreach ($namespaces_all as $key => $space) {
            $check_space = strtolower(str_replace([' ', "\r", "\n"], '', $space));
            if (in_array($check_space, $check_arr_namespace))
                unset($namespaces_all[$key]);
            else
                $check_arr_namespace[] = $check_space;
        }
        $source = "<?php " . PHP_EOL . join(PHP_EOL, $namespaces_all) . PHP_EOL . $source;

        file_put_contents(DIRNAME . "/" . $file . ".php", $source);
        if ($is_write)
            echo "Процесс компиляции завершён" . PHP_EOL;
        return $source;
    }

    public function load($compile) {
        $this->config = $compile;
        return $this;
    }

    public function setDefaultColor($color) {
        if (in_array($color, ['white', 'green', 'red', 'blue']))
            $this->color = $color;
        else
            throw new SimpleVkException(0, "Неверное название цвета");
        return $this;
    }

    public function setCaseDefault($case = true) {
        $this->case_default = $case;
        return $this;
    }

    public function isTextBtnTriggered($status = true) {
        $this->is_text_button_triggered = $status;
        return $this;
    }

    public function msg() {
        return Message::create($this->vk, $v, $this, $this->config['btn']);
    }

    public function editBtn($id, $is_save = false) {
        if (!isset($this->config['btn'][$id]))
            throw new SimpleVkException(0, "Кнопка с id '$id' не найдена");
        return Button::create($this->config['btn'][$id], $is_save, $id);
    }

    private function runAction($id, $user_id, $action_id, $result_parse = null, $id_message = null, $is_edit = false) {
        if (isset($this->config['action'][$action_id]['access'])) {
            $flag = false;
            foreach ($this->config['action'][$action_id]['access'] as $access)
                if ((is_array($access) and $access[0] == $id and in_array($user_id, $access)) or (is_numeric($access) and ($id == $access or $user_id == $access))) {
                    $flag = true;
                    break;
                }
            if (!$flag)
                return null;
        }
        if (isset($this->config['action'][$action_id]['not_access']))
            foreach ($this->config['action'][$action_id]['not_access'] as $access)
                if ((is_array($access) and $access[0] == $id and in_array($user_id, $access)) or (is_numeric($access) and ($id == $access or $user_id == $access)))
                    return null;
        $this->status = 0;
        $is_edit = $is_edit || ($this->config['action'][$action_id]['is_edit'] ?? false);
        if ($is_edit) {
            if ($id_message['type'])
                $result = Message::create($this->vk, $this->config['action'][$action_id], $this, $this->config['btn'], $action_id)->sendEdit($id, $id_message['id'], null, $result_parse);
            else
                $result = Message::create($this->vk, $this->config['action'][$action_id], $this, $this->config['btn'], $action_id)->sendEdit($id, null, $id_message['id'], $result_parse);
        } else
            $result = Message::create($this->vk, $this->config['action'][$action_id], $this, $this->config['btn'], $action_id)->send($id, null, $result_parse);
        $this->status = 0;
        return $result;
    }

    private function getFunction($id, $type) {
        $func_info = new \ReflectionFunction($this->config['action'][$id][$type]);
        $filename = $func_info->getFileName();
        $start_line = $func_info->getStartLine() - 1;
        $end_line = $func_info->getEndLine();
        $length = $end_line - $start_line;

        if (!in_array($filename, $this->compile_files))
            $this->compile_files[] = $filename;

        $source = file($filename);
        $body = implode("", array_slice($source, $start_line, $length));
        $tokens = token_get_all("<?php " . $body);
        $flag = 0;
        $brackets = 0;
        $result = '';
        $type = $type == 'func' ? 'func' : 'afterFunc';
        $cache = '';
        foreach ($tokens as $token) {
            if (is_string($token))
                $var = $token;
            else {
                if ($token[0] == T_DOC_COMMENT or $token[0] == T_COMMENT)
                    continue;
                else if ($token[0] != T_CONSTANT_ENCAPSED_STRING)
                    $var = str_replace(["\t", "\r", "\n"], '', $token[1]);
                else
                    $var = $token[1];
            }
            if ($flag > 0) {
                $result .= $var;
                if ($var == '{') {
                    ++$brackets;
                    $flag = 2;
                } else if ($var == '}')
                    --$brackets;
                if ($brackets == 0 and $flag == 2)
                    $flag = 0;
            } else if ($var == $type) {
                $flag = -1;
                $cache = $result;
                $result = '';
            } else if ($var == 'function' and ($result == '' or $flag == -1)) {
                $flag = 1;
                $cache = $result;
                $result = $var;
            }
        }
        return $brackets == 0 ? $result : $cache;
    }

    public function editRun($send, $id, $id_message) {
        if (!is_numeric($id_message))
            throw new SimpleVkException(0, "Не пришёл id сообщения");
        if (empty($this->config['action'][$send]))
            throw new SimpleVkException(0, "Событие $send не найдено");
        $this->vk->initUserID($user_id)->initPayload($payload);
        return $this->runAction($id, $user_id, $send, $payload, ['id' => $id_message, 'type' => true], true);
    }

    public function run($send = null, $id = null) {
        $data = $this->vk->initVars($id_now, $user_id, $type, $message, $payload);;
        $id = $id ?? $id_now;
        if (isset($send))
            if (isset($this->config['action'][$send]))
                return $this->runAction($id, $user_id, $send, $payload);
            else
                throw new SimpleVkException(0, "События с ID '$send' не существует");
        if (!in_array($type, $this->events))
            return null;
        $message_id = ['id' => $data['object']['conversation_message_id'] ?? null, 'type' => false];
        if (isset($payload['name']) and isset($this->config['action'][$payload['name']]))
            return $this->runAction($id, $user_id, $payload['name'], $payload, $message_id);
        if ((isset($payload['command']) and $payload['command'] == 'start') or $this->is_text_start)
            return $this->runAction($id, $user_id, 'first', $payload, $message_id);
        if (!empty($message)) {
            if (isset($this->config['mask'])) {
                $arr_msg = explode(' ', $message);
                foreach ($this->config['mask'] as $action => $masks)
                    foreach ($masks[0] as $mask) {
                        $mask_words = explode(' ', $mask);
                        if (count($mask_words) != count($arr_msg))
                            continue;
                        $flag = true;
                        $result_parse = [];
                        foreach ($mask_words as $index => $word) {
                            if ($word == '%n') {
                                $number_temp = str_replace(",", '.', $arr_msg[$index]);
                                if (is_numeric($number_temp))
                                    $result_parse[] = (double)$number_temp;
                                else {
                                    $flag = false;
                                    break;
                                }
                            } else if ($word == '%s' and is_string($arr_msg[$index])) {
                                $result_parse[] = $arr_msg[$index];
                            } else if ((!$masks[1] or $word != $arr_msg[$index]) and ($masks[1] or $word != mb_strtolower($arr_msg[$index]))) {
                                $flag = false;
                                break;
                            }
                        }
                        if ($flag)
                            return $this->runAction($id, $user_id, $action, $result_parse, $message_id);
                    }
            }
            if (isset($this->config['preg_mask']))
                foreach ($this->config['preg_mask'] as $action => $preg_mask)
                    if (preg_match($preg_mask, $message, $result_parse))
                        return $this->runAction($id, $user_id, $action, $result_parse, $message_id);
        }
        if (isset($this->config['action']['other']))
            return $this->runAction($id, $user_id, 'other', null, $message_id);
        return null;
    }
}

class Button {
    private $config;
    private $id;

    public function __construct(&$config, $is_save, $id) {
        if ($is_save)
            $this->config = &$config;
        else
            $this->config = $config;
        $this->id = $id;
    }

    static function create(&$config, $is_save, $id) {
        return new self($config, $is_save, $id);
    }

    public function payload($payload) {
        if (in_array('name', array_keys($payload)))
            throw new SimpleVkException(0, "Нельзя использовать name в payload");
        $this->config[1] = array_merge($payload, ['name' => $this->id]);
        return $this;
    }

    public function addPayload($payload) {
        if (in_array('name', array_keys($payload)))
            throw new SimpleVkException(0, "Нельзя использовать name в payload");
        $this->config[1] = array_merge($this->config[1] ?? [], $payload, ['name' => $this->id]);
        return $this;
    }

    public function getPayload() {
        return $this->config[1];
    }

    public function text($text) {
        if (!in_array($this->config[0], ['text', 'callback', 'open_link', 'open_app']))
            throw new SimpleVkException(0, "У этого типа кнопок нельзя задать текст");
        if ($this->config[0] == 'open_link')
            $this->config[3] = $text;
        else
            $this->config[2] = $text;
        return $this;
    }

    public function getText() {
        if (!in_array($this->config[0], ['text', 'callback', 'open_link', 'open_app']))
            throw new SimpleVkException(0, "У этого типа кнопок нельзя задать текст");
        if ($this->config[0] == 'open_link')
            return $this->config[3];
        else
            return $this->config[2];
    }

    public function link($link) {
        if ($this->config[0] != 'open_link')
            throw new SimpleVkException(0, "У этого типа кнопок нельзя задать адрес ссылке");
        $this->config[2] = $link;
        return $this;
    }

    public function getLink() {
        if ($this->config[0] != 'open_link')
            throw new SimpleVkException(0, "У этого типа кнопок нельзя задать адрес ссылке");
        return $this->config[2];
    }

    public function dump() {
        return $this->config;
    }

    public function type() {
        return $this->config['0'];
    }
}