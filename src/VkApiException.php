<?php
namespace DigitalStar\vk_api;
use Exception;


class VkApiException extends Exception
{
    function __construct($message)
    {
        parent::__construct($message);
    }
}