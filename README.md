# Документация по vk_api
![](https://img.shields.io/packagist/php-v/digitalstars/vk_api.svg?color=FF6F61)
![](https://img.shields.io/badge/VK_API-%3E=%205.80-8992bb.svg)
![](https://img.shields.io/github/release/digitalstars/vk_api.svg?color=green)
![](https://img.shields.io/github/commit-activity/m/digitalstars/vk_api.svg)
[![](https://img.shields.io/packagist/dt/digitalstars/vk_api.svg)](https://packagist.org/packages/digitalstars/vk_api/)
[![](https://img.shields.io/badge/Чат_vk-Вступить-blue.svg)](https://vk.me/join/AJQ1dzQRUQxtfd7zSm4STOmt)

Библиотека упрощает работу с api vk.com
> Внимание!
>* Поддержка бесед работает только если выбрана версия callBack api старше 5.8
>* Библитека адаптирована для версий 5.80 и выше
## Оглавление
* [Подключение](#Подключение)
* [Описание классов и их методов](#Доступные-классы)
    * Класс [vk_api](#Класс-vk_api)
    * Класс [LongPoll](#Класс-LongPoll)
    * Класс [Execute](#Класс-Execute)
    * Класс [Coin](#Класс-Coin)
    * Класс [Group](#Класс-Group)
    * Класс [Auth](#Класс-Auth)
    * Класс [Post](#Класс-Post)
    * Класс [Message](#Класс-Message)
    * Класс [VkApiException](#Исключения-VkApiException)
* [Работа с клавиатурой](#Работа-с-клавиатурой)
* [Файл конфигурации](#Файл-конфигурации)
* [Примеры работы](#Примеры-работы)
* [План развития проекта](#План-развития-проекта)
* [Помощь проекту](#Помощь-проекту)
***********************
## Подключение
### Используя composer
```
composer require digitalstars/vk_api
```
```php
require_once "vendor/autoload.php"; //Подключаем библиотеку
```
### Вручную
1. Скачать последний релиз
2. Подключить перемещённый autoload.php
Подключение autoload.php, если ваш скрипт находится в той же папке, что и папка vk_api-master
```php
require_once "vk_api-master/autoload.php"; //Подключаем библиотеку
```
### Подключение всех классов
```php
use DigitalStar\vk_api\vk_api; // Основной класс
use DigitalStar\vk_api\Coin; // работа с vkcoins
use DigitalStar\vk_api\LongPoll; //работа с longpoll
use DigitalStar\vk_api\Execute; // Поддержка Execute
use DigitalStar\vk_api\Group; // Работа с группами с ключем пользователя
use DigitalStar\vk_api\Auth; // Авторизация
use DigitalStar\vk_api\Post; // Конструктор постов
use DigitalStar\vk_api\Message; // Конструктор сообщений
use DigitalStar\vk_api\VkApiException; // Обработка ошибок
```

## Доступные классы
### Класс vk_api
#### Инициализация класса
* `$vk = vk_api::create('токен группы или пользователя', 'версия api');` авторизация через токен группы/пользователя
* `$vk = vk_api::create('логин', 'пароль', 'версия api');` авторизация через логин/пароль пользователя
* `$vk = vk_api::create('экземпляр класса Auth', 'версия api');` использование уже готовой [авторизации](#Класс-Auth)
#### Методы класса
* `reply($message)` - отправка сообщения тому, от кого пришел callback(личка пользователя или беседа)
* `sendMessage($id, $message)` - отправка сообщения
* `sendOK()` - отправка текста 'ok', и сообщение клиенту, что-бы не ждал ответа скрипта (обход тайм-аута vk)
* [sendButton](#Работа-с-клавиатурой)`($user_id, $message, $buttons = [], $one_time = False)` - отправка клавиатуры _(только если отправитель - бот)_
    * `$user_id` - id пользователя, которому нужно отправить клавиатуру
    * `$message` - сообщение, прикреплённое к клавиатуре(обязательный параметр)
    * `$buttons` - массив клавиатуры
    * `$one_time` - исчезнет ли клавиатура после того, как пользователь ей воспользуется?
* `groupInfo($group_url)` - Возвращает информацию о группе
    * `$group_url` - ссылка на группу в любом виде или id группы
* `userInfo($user_url = null, $scope = [])` - Возвращает информацию о пользователе
    * `$user_url` - ссылка на пользователя в любом виде или его id
    * `$scope` - дополнительные параметры запроса к api, в формате: \['параметр' => 'значение',...]
* `request($method, $params = [])` - универсальная функция для работы с любыми методами api vk
    * `$method` - метод
    * `$params` - параметры в формате: \['параметр' => 'значение',...]
* `sendImage($id, $local_file_path)` - отправка изображения
    * `$id` - id того, кому отправится сообщение
    * `$local_file_path` - путь до изображения
* `uploadDocsGroup($groupID, $local_file_path, $title = null)` - загрузка документа в документы сообщества
    * `$groupID` - id сообщества
    * `$local_file_path` - путь до файла
    * `$title` - название файла (если не указать, то останется локальное название)
* `uploadDocsUser($local_file_path, $title = null)` - загрузка документа в документы пользователя _(только с ключем пользователя)_
    * `$local_file_path` - путь до файла
    * `$title` - название файла (если не указать, то останется локальное название)
* `sendDocMessage($id, $local_file_path, $title = null)` - отправка документа
    * `$id` - id получателя
    * `$local_file_path` - путь до файла
    * `$title` - название файла (если не указать, то останется локальное название)
* `getGroupsUser($id = [], $extended = 1, $props = [])` - возвращает информацию о группах пользователя _(только с ключём пользователя)_
    * `$id` - id пользователя, информацию о группах которого надо получить(если не указать, вернёт информацию о пользователе, чей токен)
    * `$extended` - (1 - подробно, 0 - нет)
    * `$props` - дополнительные параметры запроса к api, в формате: \['параметр' => 'значение',...]
    
* `setConfirm($str)` - устанавливает строку подтверждения сервера. Подтверждает автоматически
    * `$str` - строка, которую должен вернуть сервер для подтверждения
    
* `debug()` - включает режим вывода ошибок
    
* `initVars($selectors, &...$args)` - вносит некоторые данные callback в переменные
    * `$selectors` - строка с необходимыми переменными. Возможные значения: _id, user_id, message, payload, type, all_
    * `&...$args` - переменные через запятую, в которые будут вносится значения переменных из 1 параметра. Должно быть одинаковое количество аргументов, порядок важен.
  ```php
  //пример кода  
  $vk = vk_api::create(TOKEN, VERSION)->setConfirm(CONFIRM_STR);  
  $vk->initVars('id, message, payload, all', $id, $message, $payload, $data); //в $data содержится весь прилетевший callback
  $vk->reply($message); //отвечает пользователю или в беседу
  ```
* `sendWallComment($owner_id, $post_id, $message)` - отправляет комментарий под постом
    * `$owner_id` - id пользователя
    * `$post_id` - id поста
    * `$message` - сообщение
* `getAlias($id, $n = null);` - возвращает обращение к пользователю или группе в виде строки по типу @id123 или @public123
    * `$id` - id пользователя или группы, так же можно указать короткий адрес
    * `$n` - принимает 3 параметра или можно не указывать
        * `если не указать` - вернет обращение в виде id
        * `true` - вернет Имя и Фамилию пользователя или название группы. Пример: @id1(Павел Дуров)
        * `false` - вернет только Имя пользователя или название группы Пример: @id1(Павел)
        * `любая кастомная строка, например Котик` - Пример: @id1(Котик)
        
* `sendAllDialogs($message)` - Отправляет сообщение во все диалоги в группе или личной странице(зависит от способа авторизации)
    * `$message` - отправляемая строка
    
* `isAdmin($id, $chat_id)` - проверяет, является ли $id админов в беседе $chat_id. Если пользователя ,нет в беседе или нет админ прав, то вылетит exception, советую сделать try catch для этой команды
    * `$id` - id пользователя
    * `$chat_id` - id чата
    
    Может вернуть следущие значения:
    * `owner` - создатель
    * `admin` - админ
    * `false` - пользователь
    * `null` - пользователя нет в беседе
    
* `setTryCountResendFile($var)` - задаёт максимальное количество попыток загрузки файла
    * У vk, бывает, с этим возникают некоторые баги и файл не загружается
    * `$var` - число попыток, по умолчанию 5, также можно настроить число по умолчанию в [файле конфигурации](#Файл-конфигурации)
* `setRequestIgnoreError($var)` - задаёт коды ошибок vk, при которых сообщение об ошибке игнорируется и отправляется повторный запрос
    * Внимание! запрос будет отправляться бесконечно, пока не получит от api vk ответ об успешном выполнении!
    * `$var` - массив кодов ошибок, по умолчанию [6,9,14], также можно настроить число по умолчанию в [файле конфигурации](#Файл-конфигурации)
******************
### Класс LongPoll
Класс позволяет работать LongPoll
#### Подключение
```php
require_once('vendor/autoload.php'); //подключаем библу
use DigitalStar\vk_api\vk_api;
use DigitalStar\vk_api\LongPoll;
```
#### Получения событий в группе
```php
$vk = vk_api::create(TOKEN, '5.95');
$vk = new LongPoll($vk);

$vk->listen(function($data)use($vk){ //в $data содержится все данные события, можно убрать, если не нужен
    $vk->initVars('id, message', $id, $message);
    $vk->reply($message);
});
```
#### Получение событий пользователя
```php
$vk = vk_api::create('login', 'password', '5.95');
$vk = new LongPoll($vk);

$vk->listen(function()use($vk){ //longpoll для пользователя
    $vk->on('new_message', function($data)use($vk) {
        $vk->initVars('id, message', $id, $message);
        $vk->reply($message);
    });
});
```
#### Методы класса
Все методы [vk_api](#Класс-vk_api)
******************
### Класс Execute
#### Подключение
```php
require_once('vendor/autoload.php'); //подключаем библу
use DigitalStar\vk_api\vk_api;
use DigitalStar\vk_api\Execute;
```
#### Инициализация класса
`$vk = new Execute($vk);`
> При использовании вместе с LongPoll следует передать этот объект при инициализации LongPoll
#### Поддерживаемые методы
* `sendMessage($id, $message)` - отправка сообщения
* [sendButton](#Работа-с-клавиатурой)`($user_id, $message, $buttons = [], $one_time = False)` - отправка клавиатуры _(только если отправитель - бот)_
    * `$user_id` - id пользователя, которому нужно отправить клавиатуру
    * `$message` - сообщение, прикреплённое к клавиатуре(обязательный параметр)
    * `$buttons` - массив клавиатуры
    * `$one_time` - исчезнет ли клавиатура после того, как пользователь ей воспользуется?
* Полная поддержка конструктора сообщений, если инициализировать конструктор от данного экземпляра
> Также будут работать все методы класса vk_api, но в обычном режиме, а не в режиме Execute
#### Специальные методы
* `exec()` - выполняет все накопленные методы (максимум 25)
> При использовании Execute, запросы к api vk накапливаются в памяти, как только количество запросов достигнет 25,
они отправятся одним запросом на api vk. Также можно запросить отправку вручную (если запросов < 25), с помощью метода
exec()

> При использовании LongPoll вместе с Execute, метод exec() вызывается автоматически после обработки каждой 'пачки'
полученных данных
*****************
### Класс Coin
#### Подключение
```php
require_once('vendor/autoload.php'); //подключаем библу
use DigitalStar\vk_api\vk_api;
use DigitalStar\vk_api\Coin;
```
#### Инициализация класса
* `$coin = new Coin(COIN_API_KEY, COIN_API_ID);`
* или
* `$сoin = Coin::create(COIN_API_KEY, COIN_API_ID);`
    * `COIN_API_KEY` - Ключ вашего магазина
    * `COIN_API_ID` - идентификатор владельца магазина.
#### Методы класса
* `sendCoins($user_id, $amount)` - отправка платежа.
* `getBalance($user_ids = [])` - получение баланса. Можно передать id или массив id. Если не задан `$user_ids`, вернет баланс магазина.
* `setName($name)` - установка имени магазина.
* `setCallBack($url = null)` - Установка callBack-сервера для примема платежей через специальную ссылку.
* `deleteCallBack()` -  удаление callBack-сервера.
* `getLogs()` - получение списка ошибок и изменений в настройках callBack.
* `verifyKeys($data)` - Сверка ключей callBack. Вернет true или false
* `getLink($sum = 0, $fixed_sum = true, $payload = 0, $to_hex = false)` - Получение специальной платежной ссылки.
    * `$sum` - сумма
    * `$fixed_sum` - Не дает возмоджности изменить сумму. Принимает true/false
    * `$payload` - полезная нагрузка для ссылки. Принимает int значение
    * `$to_hex` - кодирует в hex вид ссылку. Сделана скорее для скрытия данных от школьников. Если true - может не работать проверка ключей.
* `getStoryShop($last_tx)` - получить 100 последних транзакций на аккаунт
    * `$last_tx` - id транзакции, после которой будут браться 100 следующих транзакций
* `getStoryAccount($last_tx)` - получить 1000 последних транзакций по генерированных ссылкам функцией getLink
    * `$last_tx` - id транзакции, после которой будут браться 1000 следующих транзакций
* `getInfoTransactions($id_transactions)` - получить информацию о транзакциях. Принимает 1 id или массив id
* `initVars($from_id, $amount, $payload, $verify, $data)` - инициализирует переменные, которые есть в callback транзакции
    * `$data` - здесь находится все данные калбэка. На случай, если вам нужны переменные которые не задаются в функции
    * `$verify` - содержит true или false. Защита, если кто-то вдруг найдет путь до вашего callback скрипта
 ******************
### Класс Group

Класс позволяет работать с группами от имени пользователя (используя токен доступа пользователя)
#### Инициализация класса
* `$my_group = new group('id группы', 'экземпляр класса vk_api')`
#### Методы класса
Все методы [vk_api](#Класс-vk_api), которые можно использовать с ключём доступа сообщества
*******************
### Класс Auth
Класс нужен для кастомной авторизации в vk, можно задать множество параметров при авторизации и выбрать метод авторизации, также получить токен доступа или куки
#### Инициализация класса
* `$my_auth = new Auth('логин', 'пароль', $other = null, $mobile = true)` - авторизация через логин и пароль
    * Это инициализация по умолчанию при авторизации через логин/пароль в [vk_api](#Класс-vk_api)
    * $mobile - выбор метода авторизации (true - будет использоваться штатная авторизация под видом мобильного приложения, false - авторизация через приложение vk)
* `$my_auth = new Auth('куки', null, $other = null)` - авторизация через куки
    * куки - массив в json с куками
    * Работает только если куки были получены на том же сервере (с тем же ip), с которого происходит авторизация
    * Рекомендуется использовать куки, полученный при вызове метода dumpCookie()
    * Авторизацию через куки можно использовать, только если куки были получены при авторизации через приложение VK!
    
```
$other - массив для изменения значений по умолчанию
может принимать значения: ['useragent' => 'пользовательский User-Agent', 'id_app' => 'id приложения для авторизации через приложение vk'] 
Причём как что-то одно, так и все сразу
Значения по умолчанию useragent и id_app можно также изменить в файле конфигурации
```
> Как показала практика, авторизация через приложение vk может по непонятным причинам забагаваться для аккаунта, решение пока не найдено, и непонятно что на это влияет.

> Штатная авторизация под видом мобильного приложения самая надёжная и работает всегда. Рекомендуется использовать именно её
#### Методы класса
* `auth()` - запускает процесс авторизации _(ТОЛЬКО ДЛЯ АВТОРИЗАЦИИ ЧЕРЕЗ ПРИЛОЖЕНИЕ vk)_
    * После вызова метода происходит авторизация в vk (получение авторизационнах кук), но не получение токена доступа.
    (Смысл авторизации под видом мобильного приложения заключается в получении токена доступа, по этому метод auth() бессмысленен
    для метода авторизации через мобильное приложение)
* `dumpCookie()` - возвращает текущие куки в формате JSON
* `isAuth()` - проверяет, выполнена ли авторизация _вернёт true или false_
* `getAccessToken($captcha_key = null, $captcha_sid = null)` - попытаться получить токен доступа, при успехе его и вернёт
    * Параметры не работают при авторизации через приложение VK
    * `$captcha_key` - решение каптчи
    * `$captcha_sid` - сид каптчи, полученный при ошибки ([VkApiException](#Исключения-VkApiException), можно поймать через try catch вместе со ссылкой на картинку каптчи)
    если при предыдущей попытке авторизации вылетала каптча
**************
### Класс Post
Этот класс является удобным конструктором запросов для создания и публикации постов
#### Инициализация класса
* `$new_post = new Post('экземпляр класса vk_api')`
#### Методы класса
* `setMessage($message)` - задаёт текст
* `addImage($images1, $images2,...)` - добавляет картинки во вложения
    * Может принимать как 1 параметр (массив из ссылок на файлы), так и 1 или больше отдельных параметров (строк - ссылок на файлы)
* `addProp($prop, $value)` - задаёт дополнительный(пользовательский) параметр в запросе к api
    * `$prop` - название параметра
    * `$value` - значение
* `addDocs($docs, $title = null)` - добавляет документы во вложения
    * Может работать двумя способами:
        * Принимать `$docs` - путь до файла и `$title` - новое название файла, если нужно изменить
        * Принимать массив: `[['path' => 'путь', 'title' => 'название'], ['path' => 'путь', 'title' => null],...]`
* `removeImages($images)` - удаляет из вложений картинку с путём `$images`
* `removeDocs($docs)` - удаляет из вложений документ с путём `$docs`
* `removeProp($prop)` - удаляет пользовательский параметр `$prop`
* `getMedia()` - возвращает весь массив вложений
* `getMessage()` - возвращает заданное сообщение
* `getProps()` - возвращает пользовательские параметры
* `send($id, $publish_date = null)` - публикует пост, возвращает id поста после публикации
    * `$id` - id пользователя или сообщества (если сообщества, то с минусом), на стену которого будет опубликован пост
    * `$publish_date` - дата в формате Unixtime, когда будет опубликована запись (опубликуется в отложенные до этого времени).
    Если не задать, опубликуетс сразу же
> Максимальное количество вложений - 10. Это ограничение самого VK, при превышении сгенерируется исключение

> Также метод send() можно вызывать сколько угодно раз с разными параметрами, для публикации одного и того же поста в разных местах

> При вызове нескольких методов подряд с префиксом add или remove, они будут дополнять друг друга, а не заменять
******************
### Класс Message
Этот класс является удобным конструктором запросов для создания и отпраvkи сообщений
#### Инициализация класса
* `$new_message = new Message($vk)`
    * `$vk` - экземпляр класса vk_api или group, при инициализации в группе с ключем пользователя
#### Методы класса
Все те же, что и у класса [Post](#Класс-Post), только `send()` слегка другой и есть дополнительные
* [setKeyboard](#Работа-с-клавиатурой)`($keyboard = [], $one_time = false)` - прикрепляет клавиатуру к сообщению _(только если отпровитель - бот)_
    * `$buttons` - массив клавиатуры
    * `$one_time` - исчезнет ли клавиатура после того, как пользователь ей воспользуется?
    * В целом, работает так же, как и `sendButton()`
* `getKeyboard()` - возвращает настройки прикреплённой клавиатуры
* `send($id)` - отправляет сообщение
    * `$id` - $id адресата сообщения
****************
## Исключения VkApiException
```php
try {

} catch (VkApiException $e) {
    $e->getMessage() // сообщение ошибки
}
```
> Исключение генерируется, если VK возвращает в ответ ошибку (возвращается весь JSON вывода), и в некоторых случаях библиотека
сама генерирует исключение.
*******************
## Работа с клавиатурой
### Создание кнопок:
```php
$button1_1 = [null, "white", "white"];
$button1_2 = [["animals" => 'Pig'], "blue", "blue"];
$button2_1 = [["animals" => 'Cow'], "green", "green"];
$button2_2 = [["animals" => 'Chicken'], "red", "red"];
```
#### Как описываются кнопки ?
Параметр 1: Payload - может принимать значение: ассоциативный массив или null\
Параметр 2: Надпись на кнопке - текст\
Параметр 3: Цвет кнопки - может принимать значения: white, blue, green, red
### Отправка клавиатуры:
```php
$id // ID пользователя, кому будет отправлена клавиатура, или peer_id беседы
$message // Сообщение, отправляемое вместе с клавиатурой
$buttons = [[$button, ...], ...] // Массив из отправляемый кнопок
$one_time // Не обязательный параметр. Принимает значение True или False. Если True - после нажатия клавиши клавиатуры, клавиатура исчезнет, Flase - не исчезнет. По умолчанию = False
$vk->sendButton($id, $message, $buttons, $one_time);
```
#### Пример, отправка клавиатуры с текстом "Клавиатура":
```php
$id // ID пользователя, кому будет отправлена клавиатура
[[$button, ...], ...] // Массив из отправляемый кнопок
$one_time // Не обязательный параметр. Принимает значение True или False. Если True - после нажатия клавиши клавиатуры, клавиатура исчезнет, Flase - не исчезнет. По умолчанию = False
$vk->sendButton($id, 'Клавиатура', [
	[$button1_1, $button1_2],
	[$button2_1, $button2_2]
], $one_time);
```
Кнопки будут выглядеть так:
```
[ white ] [  blue ]
[ green ] [  red  ]
```
Такой запрос:
```php
$vk->sendButton($id, 'Клавиатура', [
	[$button1_1, $button1_2, $button2_2],
	[$button2_1]
]);
```
Выведет следующие кнопки:
```
[ white ] [  blue ] [  red  ]
[           green           ]
```
### Удаление кнопок (клавиатуры из диалога):
Обращаем ваше внимание, что если передать параметр $one_time = True (см. отправка клавиатуры), клавиатура исчезнет после нажатия на одну из кнопок.\
Для того, что-бы вручную выключить клавиатуру, нужно выполнить следующий запрос:
```php
$id // ID пользователя
$message // Сообщение, отправляемое при удалении клавиатуры
$vk->sendButton($id, $message);
```
*******************
## Файл конфигурации
Файл конфигурации называется `config.php` и находится в папке vk_api\
Пользовательские параметры файла:
```php
    // массив кодов ошибок vk, при которых сообщение об ошибке игнорируется и отправляется повторный запрос к api
const REQUEST_IGNORE_ERROR = [6,9,14];
    // максимальное количество попыток загрузки файла
const COUNT_TRY_SEND_FILE = 5;

    // Auth
    // Запрашиваемые права доступа для токена пользователя по уполчанию
const DEFAULT_SCOPE = "notify,friends,photos,audio,video,stories,pages,status,notes,messages,wall,ads,offline,docs,groups,notifications,stats,email,market";
    // User-Agent по умолчанию
const DEFAULT_USERAGENT = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.86 Safari/537.36';
    // ID приложения vk по умолчанию
const DEFAULT_ID_APP = '6660888';    
```
*******************************
## Примеры работы
Файлы с некоторыми примерами работы библиотеки лежат в Examples
> При установке через composer пути менять не нужно, примеры в Examples уже подключены и будут работать

## План развития проекта
В процессе заполнения

## Помощь проекту
- Яндекс.Деньги - <money.yandex.ru/to/410014638432302>
- Дебетовая карта - 2202201272652211
- Также вы можете помочь проекту `Pull Request`'ом
