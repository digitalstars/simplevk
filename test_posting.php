<?php
require_once('vk_api/autoload.php');
use DigitalStar\vk_api\Post as Post;
use DigitalStar\vk_api\VK_api as vk_api;
use DigitalStar\vk_api\Auth as Auth;
use DigitalStar\vk_api\VkApiException as VkApiException;

//**********CONFIG**************
const VK_KEY = "Key"; //ключ авторизации через приложение
const VERSION = "5.80"; //ваша версия используемого api
const VK_USERKEY = "User Key";
//******************************

try {
//    $test = new auth('login or cookie', 'pass');
//    $vk = new vk_api('login or cookie', 'pass', VERSION);
//    $vk = new vk_api(VK_USERKEY, VERSION);
//    $vk = new vk_api($test, VERSION);

    $my_post = new Post($vk);
    $my_post->setMessage("Разных рыбин пост...");
    $my_post->addProp('from_group', 1);
    $my_post->addImage('img/goldfish.jpg', 'img/pink_salmon.jpg', 'img/plotva.jpg');
    $my_post->addImage('img/plotva.jpg');
    $my_post->addDocs('img/goldfish.jpg');
    print_r( $my_post->send('-165686210', time() + 120) );
} catch (VkApiException $e) {
    print_r($e->getMessage());
}