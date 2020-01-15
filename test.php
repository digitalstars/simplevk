<?php
require_once('simplevk/autoload.php');
use DigitalStars\SimpleVK\SimpleVK as vk;
use DigitalStars\SimpleVK\LongPoll;

const VK_KEY = '';
const ID = 89846036;

vk::debug(); //теперь дебаг делается так. Писать его нужно обязательно до создания $vk
vk::setUserLogError(ID); //id юзера, которому будут отправляться все ошибки возникшие в боте
vk::$confirm_str = '757dcbf1'; //новое подтверждение бота
vk::$secret_str = '12345'; //проверка секретной строки

$vk = vk::create(VK_KEY, '5.103');
$u_data = $vk->users_get(['user_ids' => 1]); //тоже самое, что $vk->request('users.get',['user_ids' => 1]);
$data = $vk->initVars($id, $message); //без изменений
$vk->clientSupport($keyboard, $inline,$buttons); //что доступно клиенту
$vk->reply(json_encode($buttons));
//$vk->sendMessage(ID, 123);

//$vk = new LongPoll('', '5.103'); //можно и ботов и страницы через лог пасс/токен
//$vk->listen(function($origin_data)use($vk){
//    $data = $vk->initVars($id, $message, $payload, $user_id, $type);
//    if($type == 'message_new') {
//        if ($user_id == 381434758)
//            $vk->sendMessage(381434758, 123);
//    }
//});