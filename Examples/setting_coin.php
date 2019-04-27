<?php
//require_once('../vendor/autoload.php'); //подключаем библу ЧЕРЕЗ COMPOSER
require_once('../autoload.php'); //подключаем библу
use DigitalStar\vk_api\Coin;

//**********CONFIG**************
const COIN_API_KEY = ''; //Ключ магазина, можно получить ТОЛЬКО 1 РАЗ! - vk.com/coin#create_merchant
const COIN_API_MERCHANT = 89846036;//id страницы, чей ключ используется
//******************************

$coin = Coin:create(COIN_API_KEY, COIN_API_MERCHANT);
$coin->setName('Горячие пирожки'); //установить имя магазина
$coin->setCallBack('https://domain.com/callback.php'); //установить путь до калбэк скрипта

/*выведет ссылку на пополнение баланса. Ее можете отправить юзеру
* Когда юзер оплатит, информация о платеже придет на установленный вами callback скрипт
* Реализация калбэк скрипта: https://github.com/digitalstars/vk_api/blob/master/Examples/coin_callback.php
*/
echo $coin->getLink(100, true); 
