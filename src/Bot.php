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

    public function brake() {
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
        }
        if ($this->config['btn'][$id][0] == 'text' and ($is_text_triggered or $this->is_text_button_triggered))
            $this->cmd($id, $this->config['btn'][$id][2]);
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

    private function out_array($array, $var, $_livel = null) {
        $out = $margin = '';
        $nr = "\n";
        $tab = "\t";

        if (is_null($_livel)) {
            $out .= '<?php' . PHP_EOL . '$' . $var . ' = ';
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
                        $out .= $this->out_array($row, $var, $_livel);
                    } elseif (is_null($row)) {
                        $out .= 'null';
                    } elseif (is_numeric($row)) {
                        $out .= $row;
                    } elseif (is_bool($row)) {
                        $out .= ($row) ? 'true' : 'false';
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
        $source = $this->out_array($this->config, $var);
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
        return Message::create($this->vk, $this->config['action'][$action_id], $this, $this->config['btn'], $action_id)->send($id, null, $result_parse);
    }

    public function run($send = null, $id = null) {
        $this->vk->initVars($id_now, $message, $payload, $user_id, $type);
        $id = $id ?? $id_now;
        if (isset($send) and isset($this->config['action'][$send]))
            return $this->runAction($id, $user_id, $send);
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