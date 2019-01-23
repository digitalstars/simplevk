<?php
namespace DigitalStar\vk_api;
use Exception;

require_once('autoload.php');

class VkApiException extends Exception
{
    function __construct($message)
    {
        parent::__construct($message);
    }
}