<?php

namespace DigitalStar\vk_api;

/**
 * Class Message
 * @package DigitalStar\vk_api
 */
class Message extends Base
{

    /**
     * @var array
     */
    private $keyboard = [];

    /**
     * Message constructor.
     * @param $vk_api
     */
    public function __construct($vk_api)
    {
        $this->prop_list = ['random_id', 'domain', 'chat_id', 'user_ids', 'lat', 'long', 'forward_messages',
            'sticker_id', 'payload'];
        parent::__construct($vk_api);
    }

    /**
     * @return array
     */
    public function getKeyboard()
    {
        return $this->keyboard;
    }

    /**
     * @param array $keyboard
     * @param bool $inline
     * @param bool $one_time
     */
    public function setKeyboard($keyboard = [], $inline = false, $one_time = false)
    {
        $this->keyboard = ['keyboard' => $keyboard, 'inline' => $inline, 'one_time' => $one_time];
    }

    public function addVoice()
    {
        $this->addMedia(func_get_args(), 'voice');
    }

    public function removeVoice($voice)
    {
        return $this->removeMedia($voice, 'voice');
    }

    /**
     * @param $id
     * @return mixed
     */
    public function send($id)
    {
        return $this->vk_api->createMessages($id, $this->message, $this->props, $this->media, $this->keyboard);
    }
}
