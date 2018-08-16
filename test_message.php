<?php
require_once('src/autoload.php');
use vk_api\Message as Message;
use vk_api\vk_api as vk_api;
use vk_api\VkApiException as VkApiException;

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
    print_r( $my_msg->send('105083531') );
} catch (VkApiException $e) {
    print_r($e->getMessage());
}