<?php
require_once __DIR__ . '/../autoload.php';

use DigitalStars\simplevk\Bot as Bot;
use DigitalStars\SimpleVK\LongPoll as lp;

$vk = new lp("TOKEN", "5.110");
$bot = Bot::create($vk);      // Инициализация конструктора ботов

$bot->btn('first')  // Кнопка "Начать" или любой текст
    ->text("Выберите вид")  // При активации отправит текст
    ->kbd([['animals', 'mush', 'tree'], ['random_type']]); // И клавиатуру

$bot->cmd('first', '!старт');  // Команда "!старт" на начало

$bot->btn('animals', $vk->buttonText("Животные", 'blue'))  // Кнопка с id animals
    ->text("Вы выбрали животных\nТип:")  // При нажатии атправить текст
    ->kbd([['animal_1', 'animal_2', 'animal_3'], ['animal_random'], ['back_first']]); // И клавиатуру
$bot->btn('mush', $vk->buttonText("Грибы", 'white'))->text("Вы выбрали грибы\nТип:")->kbd([['mush_1', 'mush_2', 'mush_3'], ['mush_random'], ['back_first']]);
$bot->btn('tree', $vk->buttonText("Растения", 'green'))->text("Вы выбрали деревья\nТип:")->kbd([['tree_1', 'tree_2', 'tree_3'], ['tree_random'], ['back_first']]);
$bot->btn('random_type', $vk->buttonText("Случайный тип", 'red')) // Кнопка с id random_type
    ->func(function () use ($bot) {  // При нажатии выполнить функцию
        $bot->run(['animals', 'mush', 'tree'][rand(0, 2)]);  // Выполнить переход, будто нажата кнопка с одним из случайных id
        return 1;   // Прервать выполнение кнопки с id random_type
    });

$bot->redirect('back_first', 'first')  // При нажатии на кнопку с id back_first произойдёт то же, что и при нажатии на кнопку с id first
    ->btn('back_first', $vk->buttonText("<< Назад", 'red'));  // Задаётся вид кнопки с id back_first
$bot->redirect('in_first', 'first')->btn('in_first', $vk->buttonText("В начало!", 'red'));

$bot->btn('animal_1', $vk->buttonText("Млекопитающие", 'blue'))->text("Вы выбрали Млекопитающих\nКонкретнее?")->kbd([['animal_1_1', 'animal_1_2', 'animal_1_3'],['in_first'],['to_animals']]);
$bot->btn('animal_2', $vk->buttonText("Моллюски", 'blue'))->text("Вы выбрали Млекопитающих\nКонкретнее?")->kbd([['animal_2_1', 'animal_2_2', 'animal_2_3'],['in_first'],['to_animals']]);
$bot->btn('animal_3', $vk->buttonText("Иглокожие", 'blue'))->text("Вы выбрали Млекопитающих\nКонкретнее?")->kbd([['animal_3_1', 'animal_3_2', 'animal_3_3'],['in_first'],['to_animals']]);
$bot->btn('animal_random', $vk->buttonText("Случайный вид животного", 'white'))
    ->func(function () use ($bot) {
        $bot->run(['animal_1', 'animal_2', 'animal_3'][rand(0, 2)]);
        return 1;
    });
$bot->redirect('to_animals', 'animals')->btn('to_animals', $vk->buttonText("<< Назад", 'red'));

$bot->btn('animal_1_1', $vk->buttonText('Медведь', 'blue'))->text("Вы выбрали медведя!")->img("./img/a11.jpg");
$bot->btn('animal_1_2', $vk->buttonText('Волк', 'white'))->text("Вы выбрали волка!")->img("./img/a12.jpg");
$bot->btn('animal_1_3', $vk->buttonText('Суслик', 'green'))->text("Вы выбрали суслика!")->img("./img/a13.jpg");

$bot->btn('animal_2_1', $vk->buttonText('Устрица', 'blue'))->text("Вы выбрали устрицу!")->img("./img/a21.jpg");
$bot->btn('animal_2_2', $vk->buttonText('Кальмар', 'white'))->text("Вы выбрали кальмара!")->img("./img/a22.jpg");
$bot->btn('animal_2_3', $vk->buttonText('Прудовик', 'green'))->text("Вы выбрали прудовика!")->img("./img/a23.jpg");

$bot->btn('animal_3_1', $vk->buttonText('Голотурия', 'blue'))->text("Вы выбрали голотурию!")->img("./img/a31.jpg");
$bot->btn('animal_3_2', $vk->buttonText('Морской ёж', 'white'))->text("Вы выбрали морского ежа!")->img("./img/a32.jpg");
$bot->btn('animal_3_3', $vk->buttonText('Морская звезда', 'green'))->text("Вы выбрали морскую звезду!")->img("./img/a33.jpg");

$bot->btn('mush_1', $vk->buttonText("Съедобные", 'blue'))->text("Вы выбрали Съедобные\nКонкретнее?")->kbd([['mush_1_1', 'mush_1_2', 'mush_1_3'],['in_first'],['to_mush']]);
$bot->btn('mush_2', $vk->buttonText("Условно съедобные", 'blue'))->text("Вы выбрали Условно съедобные\nКонкретнее?")->kbd([['mush_2_1', 'mush_2_2', 'mush_2_3'],['in_first'],['to_mush']]);
$bot->btn('mush_3', $vk->buttonText("Ядовитые", 'blue'))->text("Вы выбрали Ядовитые\nКонкретнее?")->kbd([['mush_3_1', 'mush_3_2', 'mush_3_3'],['in_first'],['to_mush']]);
$bot->btn('mush_random', $vk->buttonText("Случайный вид грибов", 'white'))
    ->func(function () use ($bot) {
        $bot->run(['mush_1', 'mush_2', 'mush_3'][rand(0, 2)]);
        return 1;
    });
$bot->redirect('to_mush', 'mush')->btn('to_mush', $vk->buttonText("<< Назад", 'red'));

$bot->btn('mush_1_1', $vk->buttonText('Подберёзовик', 'blue'))->text("Вы выбрали подберёзовик!")->img("./img/m11.jpg");
$bot->btn('mush_1_2', $vk->buttonText('Опята', 'white'))->text("Вы выбрали опята!")->img("./img/m12.jpg");
$bot->btn('mush_1_3', $vk->buttonText('Лисичка', 'green'))->text("Вы выбрали лисичку!")->img("./img/m13.jpg");

$bot->btn('mush_2_1', $vk->buttonText('Волнушка', 'blue'))->text("Вы выбрали волнушку!")->img("./img/m21.jpg");
$bot->btn('mush_2_2', $vk->buttonText('Свинушка', 'white'))->text("Вы выбрали свинушку!")->img("./img/m22.jpg");
$bot->btn('mush_2_3', $vk->buttonText('Валуй', 'green'))->text("Вы выбрали валуй!")->img("./img/m23.jpg");

$bot->btn('mush_3_1', $vk->buttonText('Желчный', 'blue'))->text("Вы выбрали голотурию!")->img("./img/m31.jpg");
$bot->btn('mush_3_2', $vk->buttonText('Сатанинский', 'white'))->text("Вы выбрали морского ежа!")->img("./img/m32.jpg");
$bot->btn('mush_3_3', $vk->buttonText('Мухомор', 'green'))->text("Вы выбрали мухомор!")->img("./img/m33.jpg");

$bot->btn('tree_1', $vk->buttonText("Хвойные", 'blue'))->text("Вы выбрали Хвойные\nКонкретнее?")->kbd([['tree_1_1', 'tree_1_2'],['in_first'],['to_tree']]);
$bot->btn('tree_2', $vk->buttonText("Лиственные", 'blue'))->text("Вы выбрали Лиственницу\nКонкретнее?")->kbd([['tree_2_1', 'tree_2_2', 'tree_2_3'],['in_first'],['to_tree']]);
$bot->btn('tree_3', $vk->buttonText("Кустарники", 'blue'))->text("Вы выбрали Кустарники\nКонкретнее?")->kbd([['tree_3_1', 'tree_3_2', 'tree_3_3'],['in_first'],['to_tree']]);
$bot->btn('tree_random', $vk->buttonText("Случайный вид дерева", 'white'))
    ->func(function () use ($bot) {
        $bot->run(['tree_1', 'tree_2', 'tree_3'][rand(0, 2)]);
        return 1;
    });
$bot->redirect('to_tree', 'tree')->btn('to_tree', $vk->buttonText("<< Назад", 'red'));

$bot->btn('tree_1_1', $vk->buttonText('Ель', 'blue'))->text("Вы выбрали ель!")->img("./img/t11.jpg");
$bot->btn('tree_1_2', $vk->buttonText('Сосна', 'white'))->text("Вы выбрали сосну!")->img("./img/t12.jpg");

$bot->btn('tree_2_1', $vk->buttonText('Дуб', 'blue'))->text("Вы выбрали дуб!")->img("./img/t21.jpg");
$bot->btn('tree_2_2', $vk->buttonText('Клён', 'white'))->text("Вы выбрали клён!")->img("./img/t22.jpg");
$bot->btn('tree_2_3', $vk->buttonText('Берёза', 'green'))->text("Вы выбрали берёзу!")->img("./img/t23.jpg");

$bot->btn('tree_3_1', $vk->buttonText('Яблоня', 'blue'))->text("Вы выбрали яблоню!")->img("./img/t31.jpg");
$bot->btn('tree_3_2', $vk->buttonText('Вишня', 'white'))->text("Вы выбрали вишню!")->img("./img/t32.jpg");
$bot->btn('tree_3_3', $vk->buttonText('Малина', 'green'))->text("Вы выбрали малину!")->img("./img/t33.jpg");

 // Обраюотка команд
$bot->cmd('other') // Если текст не найден среди команд
    ->text("Доступные комманды:" .  // Задача текста при вводе команды
    "\n!старт - в начало" .
    "\n!меню животных - показать животных" .
    "\n!меню ростений - показать меню ростений" .
    "\n!меню грибов - показать меню грибов");
$bot->cmd('animals', '!меню животных');  // Привязка id "animals" к команде '!меню животных'
$bot->cmd('tree', '!меню ростений');
$bot->cmd('mush', '!меню грибов');

$vk->listen(function ($data) use ($vk, $bot) {
    $bot->run();  // Инициализация бота
});