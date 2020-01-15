<?php
namespace DigitalStars\SimpleVK;
use Exception;
use Throwable;

class SimpleVkException extends Exception {
    public function __construct($code, $message, Throwable $previous = null) {
        if (!is_dir('error')) {
            if (mkdir('error')) {
                $this->printError($code, $message);
            }
        } else
            $this->printError($code, $message);
        parent::__construct(PHP_EOL.PHP_EOL."CODE: $code".PHP_EOL."MESSAGE: $message".PHP_EOL.PHP_EOL, 0, $previous);
    }

    private function printError($code, $message) {
        $error = "[Exception] ".date("d.m.y H:i:s");
        $error .= "\r\nCODE: {$code}";
        $error .= "\r\nMESSAGE: {$message}";
        $error .= "\r\nin: {$this->getFile()}:{$this->getLine()}";
        $error .= "\r\nStack trace:\r\n{$this->getTraceAsString()}\r\n\r\n";
        $file = fopen('error/error_log' . date('d-m-Y_h') . ".log", 'a');
        fwrite($file, $error);
        fclose($file);
    }

    public static function userError(SimpleVkException $e) {
        $error = "[Exception] ".date("d.m.y H:i:s");
        $error .= "\r\n{$e->getMessage()}";
        $error .= "\r\nin: {$e->getFile()}:{$e->getLine()}";
        $error .= "\r\nStack trace:\r\n{$e->getTraceAsString()}\r\n\r\n";
        return $error;
    }

    public static function nullError($message) {
        if (!is_dir('error')) {
            if (mkdir('error')) {
                self::writeToLog($message);
            }
        } else
            self::writeToLog($message);
    }

    private static function writeToLog($message) {
        $error = "[Exception] ".date("d.m.y H:i:s");
        $error .= "\r\nMESSAGE: {$message}\r\n\r\n";
        $file = fopen('error/error_log' . date('d-m-Y_h') . ".log", 'a');
        fwrite($file, $error);
        fclose($file);
    }
}
