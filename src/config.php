<?php
/**
 * Created by PhpStorm.
 * User: zerox
 * Date: 31.10.18
 * Time: 0:41
 */
namespace DigitalStar\vk_api;

const REQUEST_IGNORE_ERROR = [6,9,14];
const COUNT_TRY_SEND_FILE = 5;

/*-----Массив разницы версий--------*/

const DIFFERENCE_VERSIONS_METHOD = [
    '5.9' => [
        'messages.send' => ['random_id' => "%RANDOMIZE_INT32%"]
    ]
];