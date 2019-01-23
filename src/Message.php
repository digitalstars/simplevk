<?php
namespace DigitalStar\vk_api;

class Message extends Base
{

    private $keyboard = [];

    public function __construct($vk_api)
    {
        $this->prop_list = ['random_id', 'domain', 'chat_id', 'user_ids', 'lat', 'long', 'forward_messages',
            'sticker_id', 'payload'];
        parent::__construct($vk_api);
    }

    public function setKeyboard($keyboard = [], $one_time = false)
    {
        $this->keyboard = ['keyboard' => $keyboard, 'one_time' => $one_time];
    }

    public function getKeyboard()
    {
        return $this->keyboard;
    }

    public function send($id)
    {
        return $this->vk_api->createMessages($id, $this->message, $this->props, $this->media, $this->keyboard);
    }
}