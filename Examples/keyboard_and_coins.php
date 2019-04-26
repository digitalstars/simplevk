<?php
//require_once('../vendor/autoload.php'); //подключаем библу ЧЕРЕЗ COMPOSER
require_once('../autoload.php'); //подключаем библу

use DigitalStar\vk_api\Coin;
use DigitalStar\vk_api\vk_api;

//**********CONFIG**************
const COIN_API_KEY = ''; //Ключ магазина
const COIN_ID_VK = 211984675;//id пользователя, чей ключ используется
const VK_KEY = ""; //ключ авторизации сообщества, который вы получили
const CONFIRM_STR = ""; //ключ авторизации сообщества, который вы получили
const VERSION = "5.95"; //ваша версия используемого api
const INFO = [["command" => 'mymoney'], "Баланс", "blue"]; //Код кнопки 'Баланс'
//******************************
try {

    $vk = vk_api::create(VK_KEY, VERSION)->setConfirm(CONFIRM_STR);
    $coin = Coin::create(COIN_API_KEY, COIN_ID_VK);

    $vk->debug();
    $vk->initVars('id, message, payload, user_id', $id, $message, $payload, $user_id); //инициализация переменных

    if ($payload) { //если пришел payload
        if ($payload == 'mymoney') {
            $vk->reply('Твой баланс: ' . $coin->getBalance($user_id) . ' VKC');
        }
    } else
        $vk->sendButton($id, 'Отправил кнопку', [[INFO]]); //отправляем клавиатуру с сообщением

} catch (\DigitalStar\vk_api\VkApiException $e) {

    $error = "\r\n[Exception]: возникла ошибка:";
    $error .= "\r\n[Exception]: текст: {$e->getMessage()}";
    $error .= "\r\n[Exception]: код ошибки: {$e->getCode()}";
    $error .= "\r\n[Exception]: файл: {$e->getFile()}:{$e->getLine()}";
    $error .= "\r\n[Exception]: путь ошибки: {$e->getTraceAsString()}";

    file_put_contents('error_log' . date('d-m-Y_h-i') . ".log", $error);
}