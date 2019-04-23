<?php
/**
 * Created by PhpStorm.
 * User: Назым
 * Date: 22.04.2019
 * Time: 21:06
 */
include_once '../vendor/autoload.php';

use DigitalStar\vk_api\vk_api as vk_api;
use DigitalStar\vk_api\VKCoins as VKCoins;

//**********CONFIG**************
const COIN_API_KEY = ''; //Ключ магазина
const COIN_API_MERCHANT = 211984675;//id магазина
const VK_KEY = ""; //ключ авторизации сообщества, который вы получили
const CONFIRM_STR = ""; //ключ авторизации сообщества, который вы получили
const VERSION = "5.95"; //ваша версия используемого api
const INFO = [["command" => 'info'], "Баланс", "blue"]; //Код кнопки 'Баланс'
//******************************

$vk = vk_api::create(VK_KEY, VERSION)->setConfirm(CONFIRM_STR);
$coin = VKCoins::create(COIN_API_KEY, COIN_API_MERCHANT);

$vk->debug();
$vk->initVars('id, message, payload', $id, $message, $payload); //инициализация переменных

if ($payload) { //если пришел payload
    if ($payload == 'info') {
        $convert = function ($amount) use (&$float) {//Мини конвертер, для удобства чтения коинов
            return $float === true ? $amount / 1e3 : $amount * 1e3;
        };
        $my_balance = $coin->getBalance($id)[$id];
        $float = true; //из коинов в float
        $vk->reply('Твой баланс: ' . $convert($my_balance) . ' VKC'); //отвечает пользователю или в беседу
    }
} else
    $vk->sendButton($id, 'Отправил кнопку', [[INFO]]); //отправляем клавиатуру с сообщением
