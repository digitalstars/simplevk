<?php
require_once('src/autoload.php');
use DigitalStar\vk_api\Message as Message;
use DigitalStar\vk_api\vk_api as vk_api;
use DigitalStar\vk_api\VkApiException as VkApiException;
use DigitalStar\vk_api\group as group;
use DigitalStar\vk_api\Auth as Auth;

//**********CONFIG**************
const VK_KEY = "Key"; //ключ авторизации через приложение
const VERSION = "5.80"; //ваша версия используемого api
const VK_USERKEY = "User Key";
//******************************

$test_button = [["animals" => 'Fish'], "А какие бывают?", "blue"];

try {
//    $vk = new src('login', 'pass', VERSION);
//    $vk = new src(VK_USERKEY, VERSION);
//    $vk = new src($test, VERSION);

    /*------Отправка сообщения от имени пользователя---------*/

    $my_msg = new Message($vk);
    $my_msg->setMessage("Разных рыбин сообщение...");
    $my_msg->addImage('img/goldfish.jpg');
    $my_msg->addImage('img/pink_salmon.jpg');
    $my_msg->addDocs('img/plotva.jpg');
    print_r( $my_msg->send('105083531') );

    /*---------От имени группы-------------------*/

    $my_group = new group('148648911', $vk);

    $my_msg = new Message($my_group);
    $my_msg->setMessage("Разных рыбин сообщение...");
//    $my_msg->addImage('img/goldfish.jpg');
    $my_msg->addImage('img/pink_salmon.jpg');
    $my_msg->addDocs('img/plotva.jpg');
    $my_msg->setKeyboard([]);
    $my_msg->setKeyboard([[$test_button, $test_button],
                            [$test_button, $test_button]], true);
    print_r( $my_msg->send('105083531') );

} catch (VkApiException $e) {
    print_r($e->getMessage());
}