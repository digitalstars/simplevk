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