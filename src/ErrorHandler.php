<?php

namespace DigitalStars\SimpleVK;

require_once('config_simplevk.php');

trait ErrorHandler {
    public function setUserLogError($ids) {
        if (is_numeric($ids))
            $ids = [$ids];
        $error_handler = function ($type, $message, $file, $line) use ($ids) {
            // если ошибка попадает в отчет (при использовании оператора "@" error_reporting() вернет 0)
            if (error_reporting() & $type) {
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
                if (is_callable($ids))
                    call_user_func_array($ids, [$errors[$type], $message, $file, $line, "$errors[$type][$type] $message ($file на $line строке)"]);
                else {
                    $message = "$errors[$type][$type] $message ($file на $line строке)";
                    $this->request('messages.send', ['peer_id' => $ids, 'message' => $message, 'random_id' => 0, 'dont_parse_links' => 1]);
                }
            }
            return TRUE; // не запускаем внутренний обработчик ошибок PHP
        };

        $fatal_error_handler = function () use ($error_handler) {
            if ($error = error_get_last() AND $error['type'] & (DEFAULT_ERROR_LOG)) {
                $type = $this->normalization($error['type']);
                $message = $this->normalization($error['message']);
                $file = $this->normalization($error['file']);
                $line = $this->normalization($error['line']);
                $error_handler($type, $message, $file, $line); // запускаем обработчик ошибок
            }
        };

        $exception_handler = function ($exception) use ($error_handler) {
            $message = $this->normalization($exception->getMessage());
            $file = $this->normalization($exception->getFile());
            $line = $this->normalization($exception->getLine());
            $error_handler(1, $message, $file, $line); // запускаем обработчик ошибок
        };

        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);

        set_error_handler($error_handler);
        set_exception_handler($exception_handler);
        register_shutdown_function($fatal_error_handler);

        return $this;
    }

    private function normalization($message) {
        $message = str_replace('Stack trace', 'STACK TRACE', $message);
        $message = str_replace("Array\n", 'Array ', $message);
        $message = str_replace("\n)", ')', $message);
        $message = str_replace("\n#", "\n\n#", $message);
        $message = str_replace("): ", "): \n", $message);
        $message = preg_replace_callback("/\n */", function($search) {
            return "\n&#8288;" . str_repeat("&#8199;", (int)(mb_strlen($search[0])-1)/2);
        }, $message);
        $message = preg_replace_callback('/(?:\\\\x)([0-9A-Fa-f]+)/', function($matched) {
            return chr(hexdec($matched[1]));
        }, $message);
        return $message;
    }
}