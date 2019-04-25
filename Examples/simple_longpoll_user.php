<?php
//require_once('../vendor/autoload.php'); //подключаем библу ЧЕРЕЗ COMPOSER
require_once('../autoload.php'); //подключаем библу
use DigitalStar\vk_api\vk_api;
use DigitalStar\vk_api\LongPoll;

$vk = vk_api::create('login', 'password', '5.95');
$vk = new LongPoll($vk);

$vk->listen(function()use($vk){ //longpoll для пользователя
    $vk->on('new_message', function($data)use($vk) { //обработка входящих сообщений
        $vk->initVars('id, message', $id, $message);
        $vk->reply($message);
    });
});
