<?php


namespace DigitalStars\simplevk;

class Bot {
    use FileUploader;
    /** @var SimpleVK */
    private $vk = null;
    private $config = [];
    private $is_text_start = false;
    private $is_text_button_triggered = false;
    private $case_default = false;
    private $status = 1;
    private $color = 'white';
    private $compile_files = [];

    public function __construct($vk) {
        $this->vk = $vk;
    }

    public static function create($vk) {
        return new self($vk);
    }

    public function isStartTextTriggered($start) {
        $this->is_text_start = $start;
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

    public function btn($id, $btn = null, $is_text_triggered = false) {
        if (isset($btn)) {
            if (is_array($btn)) {
                if (!isset($btn[1]))
                    $btn[1] = $this->color;
                if (count($btn) == 2 and in_array($btn[1], ['white', 'green', 'red', 'blue']))
                    $this->config['btn'][$id] = $this->vk->buttonText($btn[0], $btn[1]);
                else
                    $this->config['btn'][$id] = $btn;
            } else
                $this->config['btn'][$id] = $this->vk->buttonText($btn, $this->color);
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
        return $this->config['action'][$id]['access'];
    }

    public function notAccess($id, $access) {
        $this->config['action'][$id]['not_access'] = $access;
        return $this;
    }

    public function getNotAccess($id) {
        return $this->config['action'][$id]['not_access'];
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
                    } elseif ($stack[0] == 'action' and ($key == 'func' or $key == 'func_after') and is_callable($row)) {
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
        $source = "<?php ".PHP_EOL.join(PHP_EOL, $namespaces_all).PHP_EOL.$source;

        file_put_contents(DIRNAME."/".$file.".php", $source);
        if ($is_write)
            echo "Процесс компиляции завершён".PHP_EOL;
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

    private function runAction($id, $user_id, $action_id, $result_parse = null) {
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
        $tokens = token_get_all("<?php ".$body);
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

    public function run($send = null, $id = null) {
        $this->vk->initVars($id_now, $message, $payload, $user_id, $type);
        $id = $id ?? $id_now;
        if (isset($send))
            if (isset($this->config['action'][$send]))
                return $this->runAction($id, $user_id, $send);
            else
                throw new SimpleVkException(0, "События с ID '$send' не существует");
        if ($type != 'message_new')
            return null;
        if (isset($payload['name']) and isset($this->config['action'][$payload['name']]))
            return $this->runAction($id, $user_id, $payload['name']);
        if ((isset($payload['command']) and $payload['command'] == 'start') or $this->is_text_start)
            return $this->runAction($id, $user_id, 'first');
        if (!empty($message)) {
            if (isset($this->config['mask'])) {
                $arr_msg = explode(' ', $message);
                foreach ($this->config['mask'] as $action => $masks)
                    foreach ($masks[0] as $mask){
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
                            return $this->runAction($id, $user_id, $action, $result_parse);
                    }
            }
            if (isset($this->config['preg_mask']))
                foreach ($this->config['preg_mask'] as $action => $preg_mask)
                    if (preg_match($preg_mask, $message, $result_parse))
                        return $this->runAction($id, $user_id, $action, $result_parse);
        }
        if (isset($this->config['action']['other']))
            return $this->runAction($id, $user_id, 'other');
        return null;
    }
}