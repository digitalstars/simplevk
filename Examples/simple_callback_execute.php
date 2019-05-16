<?php
/** данные подключения правильные только в том случае,
* если вы запускаете скрипт из папки Examples  из только что установленой либы. 
* Иначе путь до autoload будет другой
*/
//require_once('../vendor/autoload.php'); //подключаем библу ЧЕРЕЗ COMPOSER
require_once('../autoload.php'); //подключаем библу
use DigitalStar\vk_api\vk_api;
use DigitalStar\vk_api\Execute;

//**********CONFIG**************
const VK_KEY = ""; //ключ авторизации сообщества, который вы получили
const CONFIRM_STR = ""; //ключ авторизации сообщества, который вы получили
const VERSION = "5.95"; //ваша версия используемого api
//******************************

$vk = vk_api::create(VK_KEY, VERSION)->setConfirm(CONFIRM_STR);
$vk = new Execute($vk);
$vk->debug();
$vk->initVars($id, $message); //инициализация переменных
$vk->reply($message); //отвечает пользователю или в беседу
