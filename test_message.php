<?php
/**
 * Created by PhpStorm.
 * User: zerox
 * Date: 15.08.18
 * Time: 1:35
 */

include "vk_api.php"; //Подключаем библиотеку для работы с api vk

//**********CONFIG**************
const VK_KEY = "85f6fff60b018e84265283a33f2897fdfeed3531e895a478e28d4b3af268e43953782a63e8e6613d9f21a"; //ключ авторизации через приложение
const VERSION = "5.80"; //ваша версия используемого api
//******************************

$test_button = [["animals" => 'Fish'], "А какие бывают?", "blue"];

try {
    $vk = new vk_api(VK_KEY, VERSION);

    $my_msg = new message($vk);
    $my_msg->setMessage("Разных рыбин сообщение...");
    $my_msg->addImage('img/goldfish.jpg');
    $my_msg->addImage('img/pink_salmon.jpg');
    $my_msg->addDocs('img/plotva.jpg');
    $my_msg->setKeyboard([[$test_button, $test_button],
                            [$test_button, $test_button]], true);
    print_r( $my_msg->send('105083531') );
} catch (vk_apiException $e) {
    print_r($e->getMessage());
}