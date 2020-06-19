<?php


namespace DigitalStars\simplevk;


class BaseConstructor {
    protected $config;
    protected $vk = null;

    public function __construct($vk = null, &$cfg = null) {
        if (!isset($cfg))
            $this->config = [];
        else
            $this->config = &$cfg;
        $this->vk = $vk;
    }

    public function __call($name, $arguments) {
        $prefix = substr($name, 0, 2);
        $func = substr($name, 2);
        if ($prefix == 'a_')
            $prefix = 'func_after_chain';
        else if ($prefix == 'b_')
            $prefix = 'func_before_chain';
        else
            throw new SimpleVkException(0, 'Неверно задан префикс функции');
        if (is_callable($func))
            $this->config[$prefix][] = ['f' => $func, 'args' => $arguments];
        else
            throw new SimpleVkException(0, 'Функция '.$func.' недоступна');
        return $this;
    }

    public function a_sleep($time) {
        $this->config['func_after_chain'][] = ['f' => 'sleep', 'args' => [$time]];
        return $this;
    }

    public function b_sleep($time) {
        $this->config['func_before_chain'][] = ['f' => 'sleep', 'args' => [$time]];
        return $this;
    }

    public function clearChainAfter() {
        $this->config['func_after_chain'] = [];
        return $this;
    }

    public function getChainAfter() {
        return $this->config['func_after_chain'];
    }

    public function clearChainBefore() {
        $this->config['func_before_chain'] = [];
        return $this;
    }

    public function getChainBefore() {
        return $this->config['func_before_chain'];
    }

    public function text($text) {
        $this->config['text'] = $text;
        return $this;
    }

    public function img() {
        $this->config['img'] = $this->imgParse(func_get_args());
        return $this;
    }

    public function doc() {
        $this->config['doc'] = $this->docParse(func_get_args());
        return $this;
    }

    public function addDoc() {
        if (empty($this->config['doc']))
            $this->config['doc'] = $this->docParse(func_get_args());
        else
            $this->config['doc'] = array_merge($this->config['doc'], $this->docParse(func_get_args()));
        return $this;
    }

    public function addImg() {
        if (empty($this->config['img']))
            $this->config['img'] = $this->imgParse(func_get_args());
        else
            $this->config['img'] = array_merge($this->config['img'], $this->imgParse(func_get_args()));
        return $this;
    }

    private function removeEx(&$extends, $removed) {
        if (empty($removed)) {
            $extends = [];
            return $this;
        }
        foreach ($removed as $remove_item)
            foreach ($extends as $index => $extend)
                if ($extend[0] == $remove_item[0] and (empty($extend[1]) or $extend[1] == $remove_item[1])) {
                    unset($extends[$index]);
                    break;
                }
        return $this;
    }

    public function removeDoc() {
        return $this->removeEx($this->config['doc'], $this->docParse(func_get_args()));
    }

    public function removeImg() {
        return $this->removeEx($this->config['img'], $this->imgParse(func_get_args()));
    }

    public function params($params) {
        $this->config['params'] = $params;
        return $this;
    }

    public function func($func) {
        $this->config['func'] = $func;
        return $this;
    }

    public function afterFunc($func) {
        $this->config['func_after'] = $func;
        return $this;
    }

    public function getFunc() {
        return $this->config['func'];
    }

    public function getAfterFunc() {
        return $this->config['func_after'];
    }

    public function getDoc() {
        return $this->config['doc'];
    }

    public function getImg() {
        return $this->config['img'];
    }

    public function getText() {
        return $this->config['text'];
    }

    public function getParams() {
        return $this->config['params'];
    }

    public function dump() {
        return $this->config;
    }

    protected function request($method, $params = []) {
        return $this->vk->request($method, $params);
    }

    private function imgParse($imgs) {
        if (is_string($imgs))
            return [[$imgs]];
        $result = [];
        foreach ($imgs as $img)
            $result = array_merge($result, $this->imgParse($img));
        return $result;
    }

    private function docParse($docs) {
        if (is_string($docs))
            return [[$docs, null]];
        else if (isset($docs['path']))
            return [[$docs['path'], $docs['title'] ?? null]];
        $result = [];
        foreach ($docs as $doc)
            $result = array_merge($result, $this->docParse($doc));
        return $result;
    }
}