<?php
namespace DigitalStar\vk_api;
require_once('autoload.php');

class Base
{
    protected $vk_api;
    protected $message = null;
    protected $media = [];
    protected $props = [];
    protected $prop_list = [];

    protected function __construct($vk_api)
    {
        $this->vk_api = $vk_api;
    }

    public function addImage()
    {
        $this->addMedia(func_get_args(), 'images');
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function addProp($prop, $value)
    {
        if (!in_array($prop, $this->prop_list))
            return 0;
        $this->props += [$prop => $value];
        return $prop;
    }

    public function addDocs()
    {
        $this->addMedia(func_get_args(), 'docs');
    }

    protected function addMedia($media, $selector)
    {
        if ($this->countMedia()+count($media) > 10)
            throw new VkApiException('Вы превысили максимальный лимит в 10 файлов');
        else {
            if (is_array($media))
                foreach ($media as $val) {
                    if (is_array($val))
                        $this->media[$selector] += $val;
                    else
                        $this->media[$selector][] = $val;
                }
            else
                $this->$selector[] = $media;
        }

    }

    private function removeMedia($media, $selector)
    {
        $search = array_search($media, $this->media[$selector]);
        if ($search) {
            $remove_val = $this->media[$selector][$search];
            unset($this->media[$selector][$search]);
            return $remove_val;
        }
        if (is_numeric($media) and ($media >= 0 and $media <= count($this->media[$selector]) - 1)) {
            $remove_val = $this->media[$selector][$media];
            unset($this->media[$selector][$media]);
            return $remove_val;
        }
        return 0;
    }

    public function removeImages($images)
    {
        return $this->removeMedia($images, 'images');
    }

    public function removeDocs($docs)
    {
        return $this->removeMedia($docs, 'docs');
    }

    public function removeProp($prop)
    {
        $search = array_search($prop, $this->props);
        if ($search) {
            $remove_val = $this->props[$search];
            unset($this->props[$search]);
            return $remove_val;
        }
        if (is_numeric($prop) and ($prop >= 0 and $prop <= count($this->props) - 1)) {
            $remove_val = $this->props[$prop];
            unset($this->props[$prop]);
            return $remove_val;
        }
        return 0;
    }

    private function countMedia()
    {
        $count = 0;
        foreach ($this->media as $kye => $var) {
            $count += count($var);
        }
        return $count;
    }

    public function getImages()
    {
        if (isset($this->media['images']))
            return $this->media['images'];
        else return [];
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getProps()
    {
        return $this->props;
    }

}