<?php
namespace DigitalStar\vk_api;

class Post extends Base
{

    public function __construct($vk_api)
    {
        $this->prop_list = ['friends_only', 'from_group', 'services', 'signed', 'publish_date', 'lat', 'long', 'place_id',
            'post_id', 'guid', 'mark_as_ads', 'close_comments'];
        parent::__construct($vk_api);
    }

    public function send($id, $publish_date = null)
    {
        if ($publish_date >= time())
            $this->props['publish_date'] = $publish_date;
        else
            throw new VkApiException('Неверно указан $publish_date');
        return $this->vk_api->createPost($id, $this->message, $this->props, $this->media);
    }
}