<?php

namespace DigitalStars\SimpleVK;

// массив кодов ошибок ВК, при которых сообщение об ошибке игнорируется и отправляется повторный запрос к api (может уйти в рекурсию)
const REQUEST_IGNORE_ERROR = [1, 6, 9, 10, 14];
// максимальное количество попыток загрузки файла
const COUNT_TRY_SEND_FILE = 5;
// Прокси по умолчанию
const PROXY = [];
// Auth
// Запрашиваемые права доступа для токена пользователя по уполчанию
const DEFAULT_SCOPE = "notify,friends,photos,audio,video,stories,pages,status,notes,messages,wall,ads,offline,docs,groups,notifications,stats,email,market,phone,exchange,leads,adsweb,wallmenu,menu";
// User-Agent по умолчанию
const DEFAULT_USERAGENT = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.86 Safari/537.36';
// Приложения ВК
const DEFAULT_APP = [
    "android" => [
        'id' => 2274003,
        'secret' => 'hHbZxrka2uZ6jB1inYsH'
    ],
    'ios' => [
        'id' => 3140623,
        'secret' => 'VeWdmVclDCtn6ihuP1nt'
    ],
    'windows' => [
        'id' => 3697615,
        'secret' => 'AlVXZFMUqyrnABp8ncuU'
    ]
];

const DEFAULT_ERROR_LOG = E_ALL; //E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR
// Автосохранение авторизации
const AUTO_SAVE_AUTH = True;
// Директория запуска корневого скрипта
DEFINE('DIRNAME', dirname(current(get_included_files())));