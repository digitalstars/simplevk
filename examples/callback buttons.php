<?php
require_once __DIR__ . '/../autoload.php';

use DigitalStars\simplevk\Bot as Bot;
use DigitalStars\SimpleVK\LongPoll as lp;

$vk = new lp("", "5.120");
$bot = Bot::create($vk);

$bot->isAllBtnCallback(true); //все кнопки по дефолту - callback

$kbd = [['link', 'app'],['edit', 'bar']];
$bot->btn('link', 'Ссылка')->eventAnswerOpenLink('https://yandex.ru'); //инициализируем кнопку link и назвачаем действия, которое произойдет после ее нажатия
$bot->btn('app', 'Приложение')->eventAnswerOpenApp(7150924, 105083531);
$bot->btn('bar', 'Snackbar')->eventAnswerSnackbar('Snackbar');
$bot->btn('edit', null)->edit()->text('callback')->kbd($kbd, true); //инициализируем пустую. При ее нажатии - изменяем сообщение, в котором она была нажата
$bot->cmd('command', '!кнопки')->text('callback')->kbd($kbd, true);// обработка команды !кнопки

$vk->listen(function ($data) use ($vk, $bot, $kbd) {
    $bot->btn('edit', rand(100,999)); //переопределяем новое название кнопки при каждом событии
    $bot->run();
});