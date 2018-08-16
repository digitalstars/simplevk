<?php
require_once('src/autoload.php');
use vk_api\Post as Post;
use vk_api\vk_api as vk_api;
use vk_api\VkApiException as VkApiException;

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
    $my_post->send('105083531', time() + 120);
} catch (VkApiException $e) {
    print_r($e->getMessage());
}