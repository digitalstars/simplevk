<?php
/**
 * Created by PhpStorm.
 * User: zerox
 * Date: 31.10.18
 * Time: 0:41
 */
namespace DigitalStar\vk_api;
// массив кодов ошибок ВК, при которых сообщение об ошибке игнорируется и отправляется повторный запрос к api
const REQUEST_IGNORE_ERROR = [1,6,9,10,14];
// максимальное количество попыток загрузки файла
const COUNT_TRY_SEND_FILE = 5;
    // Auth
    // Запрашиваемые права доступа для токена пользователя по уполчанию
const DEFAULT_SCOPE = "notify,friends,photos,audio,video,stories,pages,status,notes,messages,wall,ads,offline,docs,groups,notifications,stats,email,market";
    // User-Agent по умолчанию
const DEFAULT_USERAGENT = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.86 Safari/537.36';
    // ID приложения ВК по умолчанию
const DEFAULT_ID_APP = '6660888';
/*-----Массив разницы версий--------*/
