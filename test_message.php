<?php
use src\Message as Message;
use src\vk_api as vk_api;
use src\VkApiException as VkApiException;

//**********CONFIG**************
const VK_KEY = ""; //ключ авторизации через приложение
const VERSION = "5.80"; //ваша версия используемого api
//******************************

$test_button = [["animals" => 'Fish'], "А какие бывают?", "blue"];

try {
    $vk = new vk_api(VK_KEY, VERSION);

    $my_msg = new Message($vk);
    $my_msg->setMessage("Разных рыбин сообщение...");
    $my_msg->addImage('img/goldfish.jpg');
    $my_msg->addImage('img/pink_salmon.jpg');
    $my_msg->addDocs('img/plotva.jpg');
    $my_msg->setKeyboard([[$test_button, $test_button],
                            [$test_button, $test_button]], true);
    print_r( $my_msg->send('89846036') );
} catch (VkApiException $e) {
    print_r($e->getMessage());
}