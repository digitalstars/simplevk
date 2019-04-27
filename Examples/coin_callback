//require_once('../vendor/autoload.php'); //подключаем библу ЧЕРЕЗ COMPOSER
require_once('../autoload.php'); //подключаем библу
use DigitalStar\vk_api\Coin;

//**********CONFIG**************
const COIN_API_KEY = ''; //Ключ магазина, можно получить ТОЛЬКО 1 РАЗ! - vk.com/coin#create_merchant
const COIN_API_MERCHANT = 89846036;//id страницы, чей ключ используется
//******************************

$coin = Coin:create(COIN_API_KEY, COIN_API_MERCHANT);
$coin->initVars($from_id, $amount, $payload, $verify, $data);
if($verify)
  echo "Пользователь с id $from_id отправил вам $amount Coin";
else
  echo "Платеж не настоящий, возможно попытка взлома или вы использовали hex декодирование ссылки"
