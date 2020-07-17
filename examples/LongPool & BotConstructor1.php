<?php
require_once __DIR__ . '/../autoload.php';

use DigitalStars\simplevk\Bot as Bot;
use DigitalStars\SimpleVK\LongPoll as lp;

$vk = new lp("", "5.110");
$bot = Bot::create($vk);

$bot->btn('first')->text("Команда не найдена" .
    "\nДоступные команды: " .
    "\n!посчитай число + число \n---пример '!посчитай 5 + 5'" .
    "\n!посчитай число * число \n---пример '!посчитай 5 * 5'" .
    "\n!напиши мне слово любое_слово \n---пример '!напиши мне слово Привет!'" .
    "\n!напиши что угодно \n---пример '!напиши Какой то рандомный текст'" .
    "\n!покажи кнопку [слово] [white,blue,red,green] \n---пример '!покажи кнопку Кнопочка green'");

$bot->redirect('other', 'first');

$bot->cmd('sum', '!посчитай %n + %n')
    ->func(function ($msg, $params) {
        $msg->text($params[0] + $params[1]);
    });

$bot->cmd('multiply', '!посчитай %n * %n')
    ->func(function ($msg, $params) {
        $msg->text($params[0] * $params[1]);
    });

$bot->cmd('word', '!напиши мне слово %s')
    ->func(function ($msg, $params) {
        $msg->text("Ваше слово: ".$params[0]);
    });

$bot->preg_cmd('more_word', "!\!напиши (.*)!")
    ->func(function ($msg, $params) {
        $msg->text("Ваше предложение: ".$params[1]);
    });

$bot->cmd('send_btn', '!покажи кнопку %s %s')->text('Ваша кнопка: ')
    ->func(function ($msg, $params) use ($vk) {
        if (!in_array($params[1], ['white', 'blue', 'red', 'green'])) {
            $msg->text("Цвет ".$params[1]." не существует, использую 'white'\n".$msg->getText());
            $params[1] = 'white';
        }
        $msg->kbd([[$vk->buttonText($params[0], $params[1])]]);
    });

$vk->listen(function ($data) use ($vk, $bot) {
    echo $bot->run() . "\n";
});