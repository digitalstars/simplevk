<?php
//Игра крестики-нолики, работающая как в беседах, так и в ЛС на callback кнопках. Можно играть даже партию, если люди в разных беседах.
//Работает без использования базы данных, все данные игры хранятся внутри payload, а долгосрочные данные в Store модуле
require_once __DIR__ . '/../autoload.php';

use DigitalStars\simplevk\{Bot, LongPoll, Store, SimpleVK};

$tokens = [
    '',
    '',
    '',
    '',
    '',
    '',
    '',
];

$vk = SimpleVK::create($tokens[array_rand($tokens)], "5.126")->setConfirm('4a1855e7');
$vk->setUserLogError(89846036); // В случае ошибки отправить её на этот id vk
$bot = Bot::create($vk)->isAllBtnCallback(); // Инициализация $bot и все кнопки по умолчанию Callback

$vk->initVars($null, $user_id, $type, $message, $payload);

if($type == 'message_new') {
    if($payload) {
        $vk->msg('Кнопки можно нажимать только с мобильных клиентов, поддерживающих callback кнопки!')->send();
        exit();
    }
}

try {

    function checkWin($map, $s) {   // Проверка на выигрыш или ничью
        $win_positions = [  // Все выигрышные позиции (3 символа подряд)
            $map[0], $map[1], $map[2],
            [$map[0][0], $map[1][0], $map[2][0]],
            [$map[0][1], $map[1][1], $map[2][1]],
            [$map[0][2], $map[1][2], $map[2][2]],
            [$map[0][0], $map[1][1], $map[2][2]],
            [$map[0][2], $map[1][1], $map[2][0]]];
        if (in_array([$s, $s, $s], $win_positions)) // Если найдено 3 символа подряд, то это победа
            return true;
        else
            return in_array('_', array_merge($map[0], $map[1], $map[2])) ? false : 'Ничья'; // Если НЕ найдено пустое место - Ничья, иначе игра продолжается
    }

    function getKeyboard($map, $opponent, $opponent_user_id, $msg_id, $msg_id_enemy, $symbol, $symbol_enemy, $current_hod, $active, $current_user_id = null) {  // Заполняет Payload кнопок
        global $bot;
        foreach ([0, 1, 2] as $row) {
            foreach ([0, 1, 2] as $col) {
                $kbd[$row][$col] = $bot->editBtn($map[$row][$col])->payload( // Получает кнопку бота по ID и изменяет её пейлоад
                    ['row' => $row,    // Строка на которой кнопка
                        'col' => $col,  // Столбец
                        'opponent' => $opponent,  // id оппонента (peer_id)
                        'ouid' => $opponent_user_id,  // id оппонента
                        'symbol' => $symbol,  // Символ игрока
                        'symbol_enemy' => $symbol_enemy,  // Символ противника
                        'hod' => $current_hod,  // какой игрок должен ходить
                        'map' => $map,  // Карта игры
                        'active' => $active, // true/false - игра активна?
                        'msg_id' => $msg_id,  // ID сообщения с игрой
                        'msg_id_enemy' => $msg_id_enemy,
                        'cuid' => $current_user_id //текущий юзер id
                    ]  // ID сообщения противника с игрой
                )->dump();  // Получить кнопку в сыром виде
            }
        }
        return $kbd;
    }

    $bot->cmd('help', '!помощь')->text("Доступные команды:\n!поиск - начать игру\n!выход - выйти из поиска\n!помощь - показать эту справку");  // Текст справки
    $bot->redirect('first', 'help');  // Кнопка начать ведёт на справку

    $bot->cmd('search', '!поиск')->func(function () use ($vk, $bot) {  // Команда "!поиск"
        $vk->initVars($peer_id, $user_id, $null, $null, $payload);  // Инициализация $user_id
        $store = Store::load()->getWriteLock();  // Инициализация глобального хранилища и получение блокировки на запись
        $opponent = $store->get('wait');  // получить id юзера на поиске
        if (!$opponent) {  // Если его не существует
            $store->sset('wait', [$user_id, $peer_id]);  // Текущий юзер встаёт в очередь
            $vk->msg("Вы встали в очередь и ожидаете игру")->send();  // Ответить
            return 1;  // Прервать выполнение события
        }

        if ($opponent[0] == $user_id) {  // Если в поиске тот же, кто запросил снова поиск
            $vk->msg("Вы уже в поиске")->send();  // Ответить
            return 1; // Прервать выполнение события
        }

        $store->unset('wait');  // Очистка поиска
        if (random_int(0, 1) == 1) {
            $users1 = [$opponent[0], $user_id];
            $users2 = [$opponent[1], $peer_id];
        } else {
            $users1 = [$user_id, $opponent[0]];
            $users2 = [$peer_id, $opponent[1]];
        }

        $default_map = [['_', '_', '_'], ['_', '_', '_'], ['_', '_', '_']]; // Начальная карта игры
        if ($users2[1] > 2e9 && $users2[0] > 2e9) {
            $msg_id1 = $bot->msg()->text("Подготовка")->send([$users2[0]])[0]['conversation_message_id'];  // Отправка сообщения, в котором будет игра, и получение его id
            $kbd1 = getKeyboard($default_map, $users2[1], $users1[1], $msg_id1, $msg_id1, 'X', 'O', $users1[0], true, $users1[0]);  // Получение клавиатуры для текущего игрока
            $bot->msg()->text("Игра началась. Игроки: @id{$users1[0]}, @id{$users1[1]}\nПервым ходит: @id{$users1[0]}")->kbd($kbd1, true)->sendEdit($users2[0], null, $msg_id1); // Редактирование сообщения на игровое
        } else {
            $msg_id1 = $bot->msg()->text("Подготовка")->send([$users2[0]])[0]['conversation_message_id'];  // Отправка сообщения, в котором будет игра, и получение его id
            $msg_id2 = $bot->msg()->text("Подготовка")->send([$users2[1]])[0]['conversation_message_id'];  // Отправка сообщения, в котором будет игра, и получение его id
            $kbd1 = getKeyboard($default_map, $users2[1], $users1[1], $msg_id1, $msg_id2, 'X', 'O', $users1[0], true);  // Получение клавиатуры для текущего игрока
            $kbd2 = getKeyboard($default_map, $users2[0], $users1[0], $msg_id2, $msg_id1, 'O', 'X', $users1[0], true); // Получение клавиатуры для противника
            $bot->msg()->text("Игра началась. Игроки: @id{$users1[0]}, @id{$users1[1]}\nПервым ходит: @id{$users1[0]}")->kbd($kbd1, true)->sendEdit($users2[0], null, $msg_id1); // Редактирование сообщения на игровое
            $bot->msg()->text("Игра началась. Игроки: @id{$users1[0]}, @id{$users1[1]}\nПервым ходит: @id{$users1[0]}")->kbd($kbd2, true)->sendEdit($users2[1], null, $msg_id2); // Редактирование сообщения на игровое
        }
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

    $bot->cmd('stat', '!играстат')->func(function ($msg) use ($vk) { // Команда "!играстат" и вывод статы игрока
        $vk->initVars($null, $user_id, $null, $null, $payload); // Инициализация $user_id
        $store = Store::load($user_id); // Инициализация хранилища юзера
        $winAll = $store->get('winAll') ?? 0;  // победы
        $loseAll = $store->get('loseAll') ?? 0;  // поражения
        $msg->text("~!full~\nПобед: $winAll, поражений: $loseAll");
    });

    $bot->btn('O', ["O", 'green'])->eventAnswerSnackbar("Такой ход уже сделан"); // При клике на кнопку "O" вывести уведомление с текстом
    $bot->btn('X', ["X", 'red'])->eventAnswerSnackbar("Такой ход уже сделан"); // При клике на кнопку "X" вывести уведомление с текстом
    $bot->btn('_', ["&#4448;"])->edit()->func(function ($msg) use ($vk, $bot) { // При клике на пустую кнопку редактировать сообщение в котором была нажата кнопка
        $vk->initVars($peer_id, $user_id, $null, $null, $payload);  // Инициализация $user_id
        if (!$payload['active']) {  // Если эта игра уже закончилась
            $vk->eventAnswerSnackbar('Игра завершена');  // Отправить уведомление
            return 1; // Прервать выполнение
        }
        if ($user_id != $payload['hod']) {  // Если сейчас не ход текущего игрока
            $vk->eventAnswerSnackbar('Сейчас не ваш ход');  // Отправить уведомление
            return 1; // Прервать выполнение
        }
        $msg2 = $bot->msg();  // Сообщение для противника
        $map = $payload['map'];  // Карта
        $map[$payload['row']][$payload['col']] = $payload['symbol']; // Добавить ход на корту
        $check_win = checkWin($map, $payload['symbol']);  // Получить статус (победа/ничья/продолжение игры)
        $store = Store::load($user_id); // Инициализация глобального хранилища и получение блокировки на запись
        $store2 = Store::load($payload['ouid']); // Инициализация глобального хранилища и получение блокировки на запись
        if ($check_win === true) {  // Если это победа
            $win = $store->get('win');
            if(isset($win[$payload['ouid']]))
                $win[$payload['ouid']] += 1;
            else
                $win[$payload['ouid']] = 1;
            $winAll = $store->get('winAll');
            $store->set('win', $win); //увеличиваем счетчик побед против этого игрока
            if($winAll) {
                $store->sset('winAll', $winAll++); //увеличиваем общий счетчик побед игрока
            } else {
                $store->sset('winAll', 1);
            }
            $loseAll2 = $store2->get('loseAll');
            if($loseAll2) {
                $store2->sset('loseAll', $loseAll2++);
            } else {
                $store2->sset('loseAll', 1);
            }

            $win2 = $store2->get('win')[$user_id] ?? 0;
            $msg->text("Победил ~!ln|$user_id~(".$win[$payload['ouid']]."), проиграл ~!ln|$payload[ouid]~(".$win2.")");  // Текст ответного сообщения
            $msg2->text("Победил ~!ln|$user_id~(".$win[$payload['ouid']]."), проиграл ~!ln|$payload[ouid]~(".$win2.")");  // Текст сообщения для противника
            $active = false;  // Игра завершена
        } else if ($check_win === 'Ничья') {  // Если это ничья
            $win = $store->get('win')[$user_id] ?? 0;
            $win2 = $store->get('win')[$payload['ouid']] ?? 0;
            $msg->text("Ничья!\n~!ln|$user_id~($win2)\n~!ln|$payload[ouid]~($win)");  // Текст ответного сообщения
            $msg2->text("Ничья!\n~!ln|$user_id~($win2)\n~!ln|$payload[ouid]~($win)");  // Текст сообщения для противника
            $active = false;  // Игра завершена
        } else {
            if ($payload['symbol'] == 'X') {
                $next_symbol = 'нолик';
            } else {
                $next_symbol = 'крестик';
            }
            $msg->text("Ход @id$payload[ouid]! ($next_symbol)");  // Текст ответного сообщения
            $msg2->text("Ход @id$payload[ouid]! ($next_symbol)");  // Текст сообщения противника
            $active = true;  // Игра продолжается
        }

        // Получить и добавить клавиатуру к ответному сообщению
        if (!$payload['cuid']) {
            $msg->kbd(getKeyboard($map, $payload['opponent'], $payload['ouid'], $payload['msg_id'], $payload['msg_id_enemy'], $payload['symbol'], $payload['symbol_enemy'], false, $active), true);  // Получить и добавить клавиатуру к ответному сообщению
            $msg2->kbd(getKeyboard($map, $peer_id, $user_id, $payload['msg_id_enemy'], $payload['msg_id'], $payload['symbol_enemy'], $payload['symbol'], true, $active), true)  //  Получить и добавить клавиатуру к сообщению противнику
            ->sendEdit($payload['opponent'], null, $payload['msg_id_enemy']);  // Редактировать сообщение противника
        } else {
            $msg->kbd(getKeyboard($map, $peer_id, $user_id, $payload['msg_id_enemy'], $payload['msg_id'], $payload['symbol_enemy'], $payload['symbol'], $payload['ouid'], $active, $payload['ouid']), true);
        }

    });  // Ответное сообщение и так отредактирует сообщение с игрой, благодаря команде ->edit() при инициализации события

} catch (Exception $e) {;}

$bot->run();  // Обработать событие
