<?php
require_once('vk_api/autoload.php'); //подключение новой библиотеки
use DigitalStar\vk_api\vk_api as vk_api;

//**********CONFIG**************
const VK_KEY = "your_key"; //тот самый длинный ключ доступа сообщества
const ACCESS_KEY = "your_key"; //например c40b9566, введите свой
const VERSION = "5.80"; //ваша версия используемого api
//******************************

const BTN_FISH =  [["animals" => 'Fish'], "А какие бывают?", "blue"]; //Код кнопки 'Fish'
const BTN_BACK =  [["command" => 'start'], "<< Назад", "red"]; // Код кнопки '<< Назад'
const BTN_SALMON = [["animals" => 'Pink_salmon'], "Горбуша", "white"]; // Код кнопки 'Горбуша'
const BTN_GOLDFISH = [["animals" => 'Goldfish'], "Золотая рыбка", "blue"]; // Код кнопки 'Золотая рыбка'
const BTN_PLOTVA = [["animals" => 'Plotva'], "Плотва", "green"]; // Код кнопки 'Плотва'

$vk = new vk_api(VK_KEY, VERSION); // создание экземпляра класса работы с api, принимает ключ и версию api
$data = json_decode(file_get_contents('php://input')); //Получает и декодирует JSON пришедший из ВК

if ($data->type == 'confirmation') { //Если vk запрашивает ключ
	exit(ACCESS_KEY); //Завершаем скрипт отправкой ключа
}

$vk->sendOK(); //Говорим vk, что мы приняли callback

if (isset($data->type) and $data->type == 'message_new') { //Проверяем, если это сообщение от пользователя
	$id = $data->object->from_id; //Получаем id пользователя, который написал сообщение
	$message = $data->object->text;

	if (isset($data->object->peer_id))
        $peer_id = $data->object->peer_id; // Получаем peer_id чата, откуда прилитело сообщение
    else
        $peer_id = $id;
	
	if (isset($data->object->payload)){  //получаем payload
        	$payload = json_decode($data->object->payload, True);
   	} else {
      		$payload = null;
   	}
  
	if (isset($payload['command']) or strtolower($message) == 'начать') { //Если нажата кнопка начать или << назад
		$vk->sendButton($peer_id, 'Хочешь посмотреть на рыбок?', [[BTN_FISH]]); //Отправляем кнопку пользователю
	} else {
		if ($payload != null) { // если payload существует
			switch ($payload['animals']) { //Смотрим что в payload кнопках
				case 'Fish': //Если это Fish
					$vk->sendButton($peer_id, 'Вот такие, выбирай', [ //Отправляем кнопки пользователю
						[BTN_SALMON, BTN_GOLDFISH, BTN_PLOTVA],
						[BTN_BACK]
					]);
					break;
				case 'Pink_salmon': //Если это Горбуша
					$vk->sendMessage($peer_id, "Держи свою горбушу!"); //отправляем сообщение
					$vk->sendImage($peer_id, "img/pink_salmon.jpg"); //отправляем картинку
					break;
				case 'Goldfish': //Если это Золотая рыбка
					$vk->sendMessage($peer_id, "Она исполнит твои желания...");
					$vk->sendImage($peer_id, "img/goldfish.jpg");
					break;
				case 'Plotva': //Если это Плотва
					$vk->sendMessage($peer_id, "Ой, похоже картинку перепутали)");
					$vk->sendImage($peer_id, "img/plotva.jpg");
					break;
				default:
					break;
			}
		}
	}
}
?>
