<p align="center">
  <img alt="SimpleVK logo" title="SimpleVK это PHP библиотека быстрой разработки ботов для VK.COM" src="http://images.vfl.ru/ii/1563283715/1c6a23fb/27226348.png"/>
</p>

<p align="center">
<img src="https://img.shields.io/packagist/php-v/digitalstars/vk_api.svg?color=FF6F61" alt="php version">
<img src="https://img.shields.io/badge/VK_API-%3E=%205.103-8992bb.svg" alt="VK api version">
<img src="https://img.shields.io/github/release/digitalstars/vk_api.svg?color=green" alt="Latest Stable Version">
<a href="https://packagist.org/packages/digitalstars/vk_api/"><img src="https://img.shields.io/packagist/dt/digitalstars/vk_api.svg" alt="VK api version"></a>
<img src="https://poser.pugx.org/laravel/framework/license.svg" alt="License">
</p> 

# SimpleVK
Беседа Беты: https://vk.me/join/AJQ1d3QhPhLNRfgrG7PGHNDY
Там публикую инфу, как тестировать новую либу. Документации к этой ветке не будет до релиза.
# Класс Auth
## Инициализация
**Входные параметры** *(Не обязательные)*
* `$login` - Логин пользователя *(строка)*
* `$pass` - Пароль пользователя *(строка)*
```php
use DigitalStars\simplevk\Auth as Auth;
$auth = Auth::create($login, $pass); 
```
> Это не авторизация, а лишь создание объекта настроек. Для авторизации используйте один из методов: getAccessToken или auth
## Методы цепочки вызовов
### login
**Входные параметры**
* `$login` - Логин пользователя *(строка)*
> Задаёт логин пользователя
### pass
**Входные параметры**
* `$pass` - Пароль пользователя *(строка)*
> Задаёт пароль пользователя
#### Пример. Поиск верных данных авторизации
```php
$login_arr = [
    ['login1', 'pass1'],
    ['login2', 'pass2'],
    ['login3', 'pass3'],
    ['login4', 'pass4']
];
$auth = Auth::create();
foreach ($login_arr as $login) {
    try {
        $token = $auth->login($login[0])->pass($login[1])->getAccessToken();
        echo "\nВерные данные: $login[0]:$login[1]";
    } catch (Exception $e) {
        continue; // Не удалось авторизоваться
    }
}
```
### cookie
**Входные параметры**
* `$cookie` - JSON куки авторизованного пользователя *(строка)*
> Задаёт куки авторизованного пользователя
```php
$auth = Auth::create('login', 'pass')->cookie('JSON');
```
### useragent
**Входные параметры**
* `$useragent` - желаемый useragent *(строка)*
> Задаёт useragent для авторизации. 

>Значение по умолчанию - константа DEFAULT_USERAGENT в файле конфигураций
```php
$auth = Auth::create('login', 'pass')->useragent('User-Agent');
```
### app
**Входные параметры**
* `$app` - id приложения для авторизации через приложение или один из идентификаторов ['windows', 'mac', 'android'] для авторизации через официальное приложение *(int или строка)*
> Задаёт метод авторизации и id приложения. 

>По умолчанию используется авторизация через официальное приложение android
```php
$auth = Auth::create('login', 'pass')->app(1234567);
```
```php
$auth = Auth::create('login', 'pass')->app('ios');
```
### scope
**Входные параметры**
* `$scope` - Список прав через запятую, получаемых при авторизации *(строка)*
> Задаёт список прав, которые будут получены при авторизации.

>Значение по умолчанию - константа DEFAULT_SCOPE в файле конфигураций
```php
$auth = Auth::create('login', 'pass')->scope('notify,friends,photos');
```
### save
**Входные параметры**
* `$is` - Сохранять ли авторизационные данные *(bool)*
> Если true - авторизация будет получена только 1 раз и сохранена в кеш. Если авторизация из кеша устареет, то будет получена повторно.

> Авторизация сохраняется в папку `cache` в директории запускаемого скрипта

> По умолчанию - true
```php
$auth = Auth::create('login', 'pass')->save(false);
```
### captchaHandler
**Входные параметры**
* `$func` - Анонимная функция *(функция)*
> При поимке каптчи будет вызвана переданная анонимная функция: $func($sid, $img), где $sid - сид каптчи, $img - ссылка на изображение каптчи. Функция должна вернуть решение каптчи (строка)

> Работает только при авторизации через официальное приложение
#### Пример. Поиск верных данных авторизации с обработкой каптчи
```php
$login_arr = [
    ['login1', 'pass1'],
    ['login2', 'pass2'],
    ['login3', 'pass3'],
    ['login4', 'pass4']
];
$auth = Auth::create()->captchaHandler(function ($sid, $img) {
    echo "\nРешите каптчу: $img\n";
    return trim(fgets(STDIN));
});
foreach ($login_arr as $login) {
    try {
        $token = $auth->login($login[0])->pass($login[1])->getAccessToken();
        echo "\nВерные данные: $login[0]:$login[1]";
    } catch (Exception $e) {
        continue; // Не удалось авторизоваться
    }
}
```
## Методы
### auth
> Только для авторизации через неофициаьное приложение

**Возвращает** ***(int)***
* `0` - Авторизация не удалась
* `1` - Авторизовано, токен получен
* `2` - Авторизовано, токен не получен
> Авторизовывается на сайте ВК и возвращает статус авторизации.

> После метода можно получить авторизационные куки пользователя
```php
$auth = Auth::create('login', 'pass')->app(1234567);
$check_auth = $auth->auth();
```
### dumpCookie
**Возвращает** ***(строка или false)***
* `строка` - Куки в JSON
> Возвращает текущие куки если они есть, иначе false. После авторизации через неофициальное приложение вернёт куки, с которыми можно будет зайти на страницу даже из браузера (При условии одинаковых ip)
```php
$auth = Auth::create('login', 'pass')->app(1234567);
$check_auth = $auth->auth();
$cookie_auth = $auth->dumpCookie();
```
### isAuth
**Возвращает** ***(int)***
* `0` - Авторизация не удалась
* `1` - Авторизовано, токен получен
* `2` - Авторизовано, токен не получен *(только при авторизации через неофициальное приложение)*
> Возвращает статус авторизации

> Для проверки используется вызов метода users.get, по этому не желательно использовать часто. При передачи экземпляра $auth в SimpleVK он сам получит новый токен, если этот устарел
```php
$auth = Auth::create('login', 'pass');
if (!$auth->isAuth())   // Если токена нет в кеше
    $token = $auth->getAccessToken();  // Получаем токен
```
### getAccessToken
**Возвращает** ***(строка)***
* `строка` - Авторизационный токен
> Получает токен, если он ещё не был получен и возвращает его
```php
$auth = Auth::create('login', 'pass');
$token = $auth->getAccessToken();
```
### reloadToken
**Возвращает** ***(строка)***
* `строка` - Авторизационный токен
> Получает новый авторизационный токен

> Вызывается автоматически, когда авторизация устарела
```php
$auth = Auth::create('login', 'pass');
echo "Токен 1: ".$auth->getAccessToken();
$auth->reloadToken();
echo "\nТокен 2: ".$auth->getAccessToken()."\n";
```