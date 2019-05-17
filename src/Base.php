<?php

namespace DigitalStar\vk_api;

/**
 * Class Base
 * @package DigitalStar\vk_api
 */
class Base
{
    /**
     * @var
     */
    protected $vk_api;
    /**
     * @var array
     */
    protected $message = [];
    /**
     * @var array
     */
    protected $media = [];
    /**
     * @var array
     */
    protected $props = [];
    /**
     * @var array
     */
    protected $prop_list = [];

    /**
     * Base constructor.
     * @param $vk_api
     */
    protected function __construct($vk_api)
    {
        $this->vk_api = $vk_api;
    }

    /**
     * @throws VkApiException
     */
    public function addImage()
    {
        $this->addMedia(func_get_args(), 'images');
    }

    /**
     * @param $media
     * @param $selector
     * @throws VkApiException
     */
    protected function addMedia($media, $selector)
    {
        if ($this->countMedia() + count($media) > 10)
            throw new VkApiException('Вы превысили максимальный лимит в 10 файлов');
        else {
            if (is_array($media))
                foreach ($media as $val) {
                    if (is_array($val) and $selector != 'docs') {
                        if (isset($this->media[$selector]))
                            $this->media[$selector] = array_merge($this->media[$selector], $val);
                        else
                            $this->media[$selector] = $val;
                    } else
                        $this->media[$selector][] = $val;
                }
            else
                $this->media[$selector][] = $media;
        }

    }

    /**
     * @return int
     */
    private function countMedia()
    {
        $count = 0;
        foreach ($this->media as $kye => $var) {
            $count += count($var);
        }
        return $count;
    }

    /**
     * @param $prop
     * @param $value
     * @return int
     */
    public function addProp($prop, $value)
    {
        if (!in_array($prop, $this->prop_list))
            return 0;
        $this->props += [$prop => $value];
        return $prop;
    }

    /**
     * @param $docs
     * @param null $title
     * @throws VkApiException
     */
    public function addDocs($docs, $title = null)
    {
        if (is_string($docs))
            $docs = [0 => ['path' => $docs, 'title' => $title]];
        else
            foreach ($docs as $id => $file) {
                if (is_string($file))
                    $docs[$id] = ['path' => $file, 'title' => null];
            }
        $this->addMedia($docs, 'docs');
    }

    /**
     * @param $images
     * @return int
     */
    public function removeImages($images)
    {
        return $this->removeMedia($images, 'images');
    }

    /**
     * @param $media
     * @param $selector
     * @return int
     */
    protected function removeMedia($media, $selector)
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

    /**
     * @param $docs
     * @return int
     */
    public function removeDocs($docs)
    {
        return $this->removeMedia($docs, 'docs');
    }

    /**
     * @param $prop
     * @return int|mixed
     */
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

    /**
     * @return array
     */
    public function getMedia()
    {
        if (isset($this->media))
            return $this->media;
        else return [];
    }

    /**
     * @return array
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return array
     */
    public function getProps()
    {
        return $this->props;
    }

}