<?php
require_once('../../../autoload.php');
use DigitalStar\vk_api\Post;
use DigitalStar\vk_api\VkApiException;
//**********CONFIG**************
const VK_KEY = "Key";//ключ авторизации через приложение
const VERSION = "5.80";//ваша версия используемого api
const VK_USERKEY = "User Key";//например c40b9566, введите свой
//******************************
try {
//    $test = new auth('login or cookie', 'pass');
//    $vk = new src('login or cookie', 'pass', VERSION);
//    $vk = new src(VK_USERKEY, VERSION);
//    $vk = new src($test, VERSION);
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
