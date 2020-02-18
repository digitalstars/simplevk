<?php
require_once __DIR__.'/../autoload.php';

use DigitalStars\simplevk\Auth as Auth;
use DigitalStars\SimpleVK\SimpleVK as vk;
use DigitalStars\simplevk\LongPoll as LongPool;

const ID = 12345; // Отправка ошибок

vk::setProxy("socks4://149.56.1.48:8181");  // Задаём прокси
vk::debug(); //теперь дебаг делается так. Писать его нужно обязательно до создания $vk
vk::setUserLogError(ID); //id юзера, которому будут отправляться все ошибки возникшие в боте

$auth = Auth::create($login, $pass)           // Авторизация через пользователя
    ->app('ios')                        // Через официальное приложение для ios
    ->captchaHandler(function ($sid, $img) {    // Отлов каптчи при авторизации
        echo "\nIMG: $img\n";
        return trim(fgets(STDIN));      // Ожидание и ввод из консоли решения каптчи
    });

echo "Access_token: ".$auth->getAccessToken()."\n";  // Вывод токена

$lp = new LongPool($auth, '5.103');

$lp->listen(function ($data) use ($lp) {                       // Получение событий из LongPool
    $lp->initVars($id, $message, $payload, $user_id, $type);   // Парсинг полученных событий
    if ($type == 'message_new') {                                     // Если событие - новое сообщение
        $lp->reply("Тестовое сообщение");                       // Отправка ответного сообщения
    }
});