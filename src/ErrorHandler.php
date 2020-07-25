<?php

namespace DigitalStars\SimpleVK;

require_once('config_simplevk.php');

trait ErrorHandler {
    public function setUserLogError($ids) {
        $ids = is_numeric($ids) ? [$ids] : $ids;
        $error_handler = function ($errno, $errstr, $errfile, $errline) use ($ids) {
            // если ошибка попадает в отчет (при использовании оператора "@" error_reporting() вернет 0)
            if (error_reporting() & $errno) {
                $errors = [
                    E_ERROR => 'E_ERROR',
                    E_WARNING => 'E_WARNING',
                    E_PARSE => 'E_PARSE',
                    E_NOTICE => 'E_NOTICE',
                    E_CORE_ERROR => 'E_CORE_ERROR',
                    E_CORE_WARNING => 'E_CORE_WARNING',
                    E_COMPILE_ERROR => 'E_COMPILE_ERROR',
                    E_COMPILE_WARNING => 'E_COMPILE_WARNING',
                    E_USER_ERROR => 'E_USER_ERROR',
                    E_USER_WARNING => 'E_USER_WARNING',
                    E_USER_NOTICE => 'E_USER_NOTICE',
                    E_STRICT => 'E_STRICT',
                    E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
                    E_DEPRECATED => 'E_DEPRECATED',
                    E_USER_DEPRECATED => 'E_USER_DEPRECATED',
                ];
                try {
                    foreach ($ids as $id) {
                        $this->request('messages.send', ['peer_id' => $id, 'message' => "$errors[$errno][$errno] $errstr ($errfile на $errline строке)"]);
                    }
                } catch (\Exception $e) {
                }
            }
            return TRUE; // не запускаем внутренний обработчик ошибок PHP
        };

        $fatal_error_handler = function () use ($error_handler) {
            if ($error = error_get_last() AND $error['type'] & (DEFAULT_ERROR_LOG)) {
                $error_handler($error['type'], $error['message'], $error['file'], $error['line']); // запускаем обработчик ошибок
            }
        };

        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);

        set_error_handler($error_handler);
        register_shutdown_function($fatal_error_handler);

        return $this;
    }
}