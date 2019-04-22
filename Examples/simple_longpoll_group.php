<?php
require_once('../../../autoload.php');
use DigitalStar\vk_api\vk_api as vk_api;
use DigitalStar\vk_api\LongPoll as LongPoll;

$vk = vk_api::create(TOKEN, '5.95');
$vk = new LongPoll($vk);

$vk->listen(function($data)use($vk){ //в $data содержится все данные события, можно убрать, если не нужен
    $vk->initVars('id, message', $id, $message);
    $vk->reply($message);
});
