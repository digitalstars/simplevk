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
     * @param array $keyboard
     * @param bool $one_time
     */
    public function setKeyboard($keyboard = [], $one_time = false)
    {
        $this->keyboard = ['keyboard' => $keyboard, 'one_time' => $one_time];
    }

    /**
     * @return array
     */
    public function getKeyboard()
    {
        return $this->keyboard;
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