<?php
require_once __DIR__ . '/../autoload.php';

use DigitalStars\simplevk\{Auth, LongPoll, SimpleVK as vk};

vk::setProxy("socks4://149.56.1.48:8181");  // Задаём прокси

$auth = Auth::create('LOGIN', 'PASS')           // Авторизация через пользователя
->app('ios')                        // Через официальное приложение для ios
->captchaHandler(function ($sid, $img) {    // Отлов каптчи при авторизации
    echo "\nIMG: $img\n";
    return trim(fgets(STDIN));      // Ожидание и ввод из консоли решения каптчи
});

echo "Access_token: " . $auth->getAccessToken() . "\n";  // Вывод токена

$lp = LongPoll::create($auth, '5.103');
$lp->setUserLogError(12345); // В случае ошибки отправить её на этот id vk

$lp->listen(function ($data) use ($lp) {                       // Получение событий из LongPool
    $lp->initVars($id, $user_id, $type, $message, $payload, $msg_id, $attachments);   // Парсинг полученных событий
    if ($type == 'message_new') {                                     // Если событие - новое сообщение
        $lp->reply("Тестовое сообщение");                       // Отправка ответного сообщения
    }
});