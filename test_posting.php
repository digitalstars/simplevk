<?php
use src\Post as Post;
use src\vk_api as vk_api;
use src\VkApiException as VkApiException;

//**********CONFIG**************
const VK_KEY = ""; //ключ авторизации через приложение
const VERSION = "5.80"; //ваша версия используемого api
//******************************
try {
    $vk = new vk_api(VK_KEY, VERSION);

    $my_post = new Post($vk);
    $my_post->setMessage("Разных рыбин пост...");
    $my_post->addProp('from_group', 1);
    $my_post->addImage('img/goldfish.jpg', 'img/pink_salmon.jpg', 'img/plotva.jpg');
    $my_post->send('89846036', time() + 120);
} catch (VkApiException $e) {
    print_r($e->getMessage());
}