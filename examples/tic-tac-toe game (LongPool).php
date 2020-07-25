<?php
require_once __DIR__ . '/../autoload.php';

use DigitalStars\simplevk\{Bot, LongPoll, Store};

$vk = LongPoll::create("TOKEN", "5.110");
$vk->setUserLogError("ID_VK"); // В случае ошибки отправить её на этот id vk
$bot = Bot::create($vk)->isAllBtnCallback(); // Инициализация $bot и все кнопки по умолчанию Callback

function checkWin($map, $s) {   // Проверка на выигрыш или ничью
    $win_positions = [  // Все выигрышные позиции (3 символа подряд)
        $map[0], $map[1], $map[2],
        [$map[0][0], $map[1][0], $map[2][0]],
        [$map[0][1], $map[1][1], $map[2][1]],
        [$map[0][2], $map[1][2], $map[2][2]],
        [$map[0][0], $map[1][1], $map[2][2]],
        [$map[2][2], $map[1][1], $map[0][0]]];
    if (in_array([$s, $s, $s], $win_positions)) // Если найдено 3 символа подряд, то это победа
        return true;
    else
        return in_array('_', array_merge($map[0], $map[1], $map[2])) ? false : 'Ничья'; // Если НЕ найдено пустое место - Ничья, иначе игра продолжается
}

function getKeyboard($map, $opponent, $msg_id, $msg_id_enemy, $symbol, $symbol_enemy, $current_hod, $active) {  // Заполняет Payload кнопок
    global $bot;
    foreach ([0, 1, 2] as $row) {
        foreach ([0, 1, 2] as $col) {
            $kbd[$row][$col] = $bot->editBtn($map[$row][$col])->payload( // Получает кнопку бота по ID и изменяет её пейлоад
                ['row' => $row,    // Строка на которой кнопка
                    'col' => $col,  // Столбец
                    'opponent' => $opponent,  // id оппонента
                    'symbol' => $symbol,  // Символ игрока
                    'symbol_enemy' => $symbol_enemy,  // Символ противника
                    'hod' => $current_hod,  // true/false - ход игрока кто нажал на кнопку
                    'map' => $map,  // Карта игры
                    'active' => $active, // true/false - игра активна?
                    'msg_id' => $msg_id,  // ID сообщения с игрой
                    'msg_id_enemy' => $msg_id_enemy]  // ID сообщения противника с игрой
            )->dump();  // Получить кнопку в сыром виде
        }
    }
    return $kbd;
}

$bot->cmd('help', '!помощь')->text("Доступные команды:\n!поиск - начать игру\n!выход - выйти из поиска\n!помощь - показать эту справку");  // Текст справки
$bot->redirect('first', 'help');  // Кнопка начать ведёт на справку

$bot->cmd('search', '!поиск')->func(function () use ($vk, $bot) {  // Команда "!поиск"
    $vk->initVars($null, $user_id, $null, $null, $payload);  // Инициализация $user_id
    $store = Store::load()->getWriteLock();  // Инициализация глобального хранилища и получение блокировки на запись
    $opponent = $store->get('wait');  // получить id юзера на поиске
    if (!$opponent) {  // Если его не существует
        $store->sset('wait', $user_id);  // Текущий юзер встаёт в очередь
        $vk->reply("Вы встали в очередь и ожидаете игру");  // Ответить
        return 1;  // Прервать выполнение события
    }

    if ($opponent == $user_id) {  // Если в поиске тот же, кто запросил снова поиск
        $vk->reply("Вы уже в поиске");  // Ответить
        return 1; // Прервать выполнение события
    }

    $store->unset('wait');  // Очистка поиска
    $users = [$opponent, $user_id];  // Массив из id текущего игрока и противника
    shuffle($users);  // Перемешать массив

    $default_map = [['_', '_', '_'], ['_', '_', '_'], ['_', '_', '_']]; // Начальная карта игры
    $msg_id1 = $bot->msg()->text("Подготовка")->send($users[0]);  // Отправка сообщения, в котором будет игра, и получение его id
    $msg_id2 = $bot->msg()->text("Подготовка")->send($users[1]);  // Отправка сообщения, в котором будет игра, и получение его id
    $kbd1 = getKeyboard($default_map, $users[1], $msg_id1, $msg_id2, 'X', 'O', true, true);  // Получение клавиатуры для текущего игрока
    $kbd2 = getKeyboard($default_map, $users[0], $msg_id2, $msg_id1, 'O', 'X', false, true); // Получение клавиатуры для противника
    $bot->msg()->text("Игра началась. Игроки: @id{$users[0]}, @id{$users[1]}\nПервым ходит: @id{$users[0]}")->kbd($kbd1, true)->sendEdit($users[0], $msg_id1); // Редактирование сообщения на игровое
    $bot->msg()->text("Игра началась. Игроки: @id{$users[0]}, @id{$users[1]}\nПервым ходит: @id{$users[0]}")->kbd($kbd2, true)->sendEdit($users[1], $msg_id2); // Редактирование сообщения на игровое
});

$bot->cmd('exit', '!выход')->text('Вы вышли из поиска')->func(function ($msg) use ($vk) { // Команда "!выход" и текст ответа по умолчанию
    $vk->initVars($null, $user_id, $null, $null, $payload); // Инициализация $user_id
    $store = Store::load()->getWriteLock(); // Инициализация глобального хранилища и получение блокировки на запись
    $wait = $store->get('wait');  // Получение id игрока в очереди
    if ($wait == $user_id)  // Если текущий юзер в поиске
        $store->unset('wait');  // Очистить очередь
    else
        $msg->text("Выходить неоткуда"); // Изменить текст сообщения
});

$bot->btn('O', ["O", 'green'])->eventAnswerSnackbar("Такой ход уже сделан"); // При клике на кнопку "O" вывести уведомление с текстом
$bot->btn('X', ["X", 'red'])->eventAnswerSnackbar("Такой ход уже сделан"); // При клике на кнопку "X" вывести уведомление с текстом
$bot->btn('_', ["&#4448;"])->edit()->func(function ($msg) use ($vk, $bot) { // При клике на пустую кнопку редактировать сообщение в котором была нажата кнопка
    $vk->initVars($null, $user_id, $null, $null, $payload);  // Инициализация $user_id
    if (!$payload['active']) {  // Если эта игра уже закончилась
        $vk->eventAnswerSnackbar('Игра завершена');  // Отправить уведомление
        return 1; // Прервать выполнение
    }
    if (!$payload['hod']) {  // Если сейчас не ход текущего игрока
        $vk->eventAnswerSnackbar('Сейчас не ваш ход');  // Отправить уведомление
        return 1; // Прервать выполнение
    }
    $msg2 = $bot->msg();  // Сообщение для противника
    $map = $payload['map'];  // Карта
    $map[$payload['row']][$payload['col']] = $payload['symbol']; // Добавить ход на корту
    $check_win = checkWin($map, $payload['symbol']);  // Получить статус (победа/ничья/продолжение игры)
    if ($check_win === true) {  // Если это победа
        $msg->text("Вы победили!");  // Текст ответного сообщения
        $msg2->text("Вы Проиграли!");  // Текст сообщения для противника
        $active = false;  // Игра завершена
    } else if ($check_win === 'Ничья') {  // Если это ничья
        $msg->text("Ничья!");  // Текст ответного сообщения
        $msg2->text("Ничья!");  // Текст сообщения для противника
        $active = false;  // Игра завершена
    } else {
        $msg->text("Ход противника!");  // Текст ответного сообщения
        $msg2->text("Ваш Ход!");  // Текст сообщения противника
        $active = true;  // Игра продолжается
    }
    $msg->kbd(getKeyboard($map, $payload['opponent'], $payload['msg_id'], $payload['msg_id_enemy'], $payload['symbol'], $payload['symbol_enemy'], false, $active), true);  // Получить и добавить клавиатуру к ответному сообщению
    $msg2->kbd(getKeyboard($map, $user_id, $payload['msg_id_enemy'], $payload['msg_id'], $payload['symbol_enemy'], $payload['symbol'], true, $active), true)  //  Получить и добавить клавиатуру к сообщению противнику
    ->sendEdit($payload['opponent'], $payload['msg_id_enemy']);  // Редактировать сообщение противника
});  // Ответное сообщение и так отредактирует сообщение с игрой, благодаря команде ->edit() при инициализации события

$vk->isMultiThread(true);  // Обрабатывать LongPoll в многопоточном режиме (Только для Linux)
$vk->listen(function ($data) use ($vk, $bot) {  // Получить событие
    $bot->run();  // Обработать событие
});