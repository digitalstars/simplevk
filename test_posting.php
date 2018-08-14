<?php
/**
 * Created by PhpStorm.
 * User: zerox
 * Date: 14.08.18
 * Time: 0:30
 */

include "vk_api.php"; //Подключаем библиотеку для работы с api vk

//**********CONFIG**************
const VK_KEY = "ключ доступа приложения"; //ключ авторизации через приложение
const ACCESS_KEY = "your_key"; //например c40b9566, введите свой
const VERSION = "5.80"; //ваша версия используемого api
//******************************
try {
    $vk = new vk_api(VK_KEY, VERSION);

    $my_post = new post($vk);
    $my_post->setMessage("Разных рыбин пост...");
    $my_post->addProp('from_group', 1);
    $my_post->addImage('img/goldfish.jpg');
    $my_post->addImage('img/pink_salmon.jpg');
    $my_post->addImage('img/plotva.jpg');
    $my_post->send('ID пользователя ВК', time() + 120);
} catch (vk_apiException $e) {
    print_r($e->getMessage());
}