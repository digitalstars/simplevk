<?php


namespace DigitalStars\simplevk;


class Carousel {

    private $config;
    private $msg;

    public function __construct(&$config = [], $msg = null) {
        $this->config = &$config;
        if (empty($this->config['action']))
            $this->config['action'] = ['type' => 'open_photo'];
        $this->msg = $msg;
    }

    public static function create(&$config = [], $msg = null) {
        return new self($config, $msg);
    }

    public function title($title) {
        $this->config['title'] = $title;
        return $this;
    }

    public function getTitle() {
        return $this->config['title'];
    }

    public function description($description) {
        $this->config['description'] = $description;
        return $this;
    }

    public function getDescription() {
        return $this->config['description'];
    }

    public function img($img) {
        $this->config['img'] = $img;
        return $this;
    }

    public function getImg() {
        return $this->config['img'];
    }

    public function action($link = '') {
        if (empty($link))
            $this->config['action'] = ['type' => 'open_photo'];
        else
            $this->config['action'] = ['type' => 'open_link', 'link' => $link];
        return $this;
    }

    public function getAction() {
        return $this->config['action']['link'] ?? false;
    }

    public function kbd($kbd) {
        if (is_string($kbd) or (isset($kbd[0]) and is_string($kbd[0])))
            $kbd = [$kbd];
        $this->config['kbd'] = $kbd;
        return $this;
    }

    public function getKbd() {
        return $this->config['kbd'];
    }

    /** @return Message */
    public function save() {
        if (isset($this->msg))
            return $this->msg;
        else
            return null;
    }
}