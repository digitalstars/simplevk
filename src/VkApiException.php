<?php
namespace src;
use Exception;

class VkApiException extends Exception
{
    function __construct($message)
    {
        parent::__construct($message);
    }
}