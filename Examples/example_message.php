<?php
require_once('../../../autoload.php'); //подключение новой библиотеки
use DigitalStar\vk_api\Message;
use DigitalStar\vk_api\vk_api;
use DigitalStar\vk_api\VkApiException;
use DigitalStar\vk_api\Group;
//**********CONFIG**************
const VK_KEY = "Key"; //ключ авторизации через приложение
const VERSION = "5.80"; //ваша версия используемого api
const VK_USERKEY = "User_Key"; //ключ доступа пользователя
//******************************
$fish_button = [["animals" => 'Fish'], "А какие бывают?", "blue"]; //инициализация кнопки
try {
    $vk_user = new vk_api('login', 'pass', VERSION); //авторизация пользователя через логин/пароль
    //$vk_user = new src(VK_USERKEY, VERSION); //авторизация через ключ пользователя
    $vk_public = new vk_api(VK_KEY, VERSION); //авторизация через ключ группы
    /*------Отправка сообщения от имени пользователя---------*/
    $my_msg = new Message($vk_user); //объект конструктора сообщений
    $my_msg->setMessage("Разных рыбин сообщение..."); //добавить в конструктор сообщение
    $my_msg->addImage('img/goldfish.jpg'); //добавить в конструктор изображение
    $my_msg->addImage('img/pink_salmon.jpg'); //еще одно изображение
    $my_msg->addDocs('img/plotva.jpg'); //добавить изображение как документ
    $my_msg->send('чей_то_id'); //отправить пользователю
    $my_msg->send('чей_то_id'); //отправить другому полюзователю
    /*---------Отправка сообщения от имени группы, используя аккаунт пользователя-------------------*/
    $my_group = new Group('id_группы', $vk_user); //id группы где вы являетесь админом или создателем
    $my_msg = new Message($my_group);
    $my_msg->setMessage("Разных рыбин сообщение...");
    $my_msg->addImage('img/pink_salmon.jpg');
    $my_msg->addDocs('img/plotva.jpg');
    $my_msg->setKeyboard([]); //если оставить такое, то у пользователя исчезнут кнопки, которые есть сейчас
    $my_msg->setKeyboard([[$fish_button, $fish_button],
                          [$fish_button, $fish_button]], true); //а это добавит к конструктору клавиатуру
    $my_msg->send('чей_то_id');
    $my_msg->send('id_беседы'); //отправит это сообщение в беседу, куда пригласили бота. Отправит даже если бот не является админом    
    /*-----------------Простые функции отправки------------------*/
    //все, кроме отправки клавиатуры работает и с объектом пользователя
    $vk_public->sendMessage('чей_то_id', 'test'); //отправка сообщения
    $vk_public->sendButton('чей_то_id', 'test', [[$fish_button, $fish_button]]); //отправка клавиатуры
    $vk_public->sendImage('чей_то_id', 'img/pink_salmon.jpg'); //отправка клавиатуры
    $vk_public->sendDocMessage('чей_то_id', 'img/pink_salmon.jpg', 'Свое_название картинки');
    
} catch (VkApiException $e) {
    //если случится какая-то ошибка в библиотеке, то текст ошибки можно получить используя $e->getMessage()
    print_r($e->getMessage()); 
    //например можно отправить ошибку себе в вк
    //$vk_user->sendMessage('ваш_id', $e->getMessage());
}
