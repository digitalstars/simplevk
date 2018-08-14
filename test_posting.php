<?php
/**
 * Created by PhpStorm.
 * User: zerox
 * Date: 14.08.18
 * Time: 0:30
 */

include "vk_api.php"; //Подключаем библиотеку для работы с api vk

//**********CONFIG**************
const VK_KEY = "K"; //ключ авторизации через приложение
const VERSION = "5.80"; //ваша версия используемого api
//******************************
try {
    $vk = new vk_api(VK_KEY, VERSION);

    $my_post = new post($vk);
    $my_post->setMessage("Разных рыбин пост...");
    $my_post->addProp('from_group', 1);
    $my_post->addImage('img/goldfish.jpg', 'img/pink_salmon.jpg', 'img/plotva.jpg');
    $my_post->send('105083531', time() + 120);
} catch (vk_apiException $e) {
    print_r($e->getMessage());
}