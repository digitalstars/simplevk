<?php

namespace DigitalStars\SimpleVK;

require_once 'config_simplevk.php';

class Diagnostics {

    private static $canCreateFile = 0;
    private static $canWriteToFile = 0;
    private static $final_text = '';

    static public function run() {
        $EOL = self::EOL();

        if (PHP_SAPI != 'cli') {
            self::$final_text .= '<html><body style="background-color: black">';
        }

        self::$final_text .= self::cyan("Диагностика системы для работы с SimpleVK " . SIMPLEVK_VERSION, $EOL, '') . $EOL;
        self::$final_text .= self::cyan("Информация о системе", $EOL, '');

        if (PHP_MAJOR_VERSION >= 7)
            self::$final_text .= self::green("PHP: " . PHP_VERSION);
        else
            self::$final_text .= self::red("PHP: " . PHP_VERSION);

        list($type, $text) = self::webServerOrCli();
        self::$final_text .= $text;

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            self::$final_text .= self::yellow("ОС: " . PHP_OS . " (На Windows модули pcntl и posix недоступны, поэтому параллельный режим longpoll не будет работать)");
        } else {
            self::$final_text .= self::green("ОС: " . PHP_OS);
        }

        if (defined('PHP_OS_FAMILY')) {
            if (PHP_OS_FAMILY == 'Windows') {
                self::$final_text .= self::yellow("ОС_FAMILY: " . PHP_OS_FAMILY);
            } else {
                self::$final_text .= self::green("ОС_FAMILY: " . PHP_OS_FAMILY);
            }
        }

        if (is_callable('curl_init')) {
            self::$final_text .= self::green("CURL: доступен");
        } else {
            self::$final_text .= self::red("CURL: не доступен");
        }

        self::$final_text .= $EOL . self::cyan("Проверка работы с файлами", $EOL, '');

        self::checkFileJob();


        self::$final_text .= $EOL . self::cyan("Проверка активации Обязательных модулей в php.ini", $EOL, '');
        self::checkImportantModules();

        self::$final_text .= $EOL;

        self::$final_text .= $EOL . self::cyan("Проверка активации Опциональных модулей в php.ini", $EOL, '');
        self::checkNoImportantModules();

        self::$final_text .= $EOL;

        if ($type != 'cli') {
            ;//проверка заголовков
        }

        if (PHP_SAPI != 'cli')
            self::$final_text .= $EOL . self::yellow("Не забудьте удалить скрипт, чтобы другие не смогли узнать информацию о вашем сервере (если скрипт доступен в сети)", $EOL, '');

        if (PHP_SAPI != 'cli') {
            self::$final_text .= '</body></html>';
        }

        print self::$final_text;
    }

    private static function EOL() {
        if (PHP_SAPI != 'cli') {
            return '<br>';
        } else {
            return PHP_EOL;
        }
    }

    private static function checkFileJob() {
        @rmdir('test_simplevk357475');
        $result = @mkdir('test_simplevk357475');
        if ($result === false) {
            self::$final_text .= self::red("Не удалось создать папку test_simplevk357475");
        } else {
            self::$final_text .= self::green("Создание папок: разрешено");
            $result = @file_put_contents('test_simplevk357475/test.txt', '123');
            if ($result === false) {
                self::$final_text .= self::red("Не удалось создать файл ./test_simplevk357475/test.txt");
            } else {
                self::$final_text .= self::green("Создание файлов: разрешено");
                self::$canCreateFile = 1;
                $result = @file_get_contents('test_simplevk357475/test.txt');
                if ($result === false) {
                    self::$final_text .= self::red("Чтение файлов: запрещено");
                } else {
                    self::$final_text .= self::green("Чтение файлов: разрешено");
                    if ($result == 123) {
                        self::$final_text .= self::green("Запись в файлы: разрешено");
                        self::$canWriteToFile = 1;
                    } else {
                        self::$final_text .= self::red("Запись в файлы: запрещено");
                    }
                }
            }
        }
        self::deleteTest();
    }

    private static function deleteTest() {
        if (file_exists('test_simplevk357475/test.txt')) {
            $result = @unlink('test_simplevk357475/test.txt');
            if ($result === false)
                self::$final_text .= self::red("Удаление файлов: запрещено");
            else
                self::$final_text .= self::green("Удаление файлов: разрешено");
        }

        if (file_exists('test_simplevk357475')) {
            $result = @rmdir('test_simplevk357475');
            if ($result === false)
                self::$final_text .= self::red("Удаление папок: запрещено");
            else
                self::$final_text .= self::green("Удаление папок: разрешено");
        }
    }

    private static function red($string, $add = PHP_EOL, $add_first = '· '): string {
        if (PHP_SAPI == 'cli')
            return "\033[" . "0;31m" . $add_first . $string . $add . "\033[0m";
        else {
            if ($add == PHP_EOL)
                $add = '<br>';
            return '<span style="color: red">' . $add_first . $string . $add . '</span>';
        }
    }

    private static function green($string, $add = PHP_EOL, $add_first = '· '): string {
        if (PHP_SAPI == 'cli')
            return "\033[" . "0;32m" . $add_first . $string . $add . "\033[0m";
        else {
            if ($add == PHP_EOL)
                $add = '<br>';
            return '<span style="color: green">' . $add_first . $string . $add . '</span>';
        }
    }

    private static function cyan($string, $add = PHP_EOL, $add_first = '· '): string {
        if (PHP_SAPI == 'cli')
            return "\033[" . "0;36m" . $add_first . $string . $add . "\033[0m";
        else {
            if ($add == PHP_EOL)
                $add = '<br>';
            return '<span style="color: cyan">' . $add_first . $string . $add . '</span>';
        }
    }

    private static function yellow($string, $add = PHP_EOL, $add_first = '· '): string {
        if (PHP_SAPI == 'cli')
            return "\033[" . "1;33m" . $add_first . $string . $add . "\033[0m";
        else {
            if ($add == PHP_EOL)
                $add = '<br>';
            return '<span style="color: yellow">' . $add_first . $string . $add . '</span>';
        }
    }

    private static function webServerOrCli() {
        if (PHP_SAPI == 'cli') {
            $text = self::green('Запущен через: ' . PHP_SAPI);
        } else if (isset($_SERVER['DOCUMENT_ROOT']) && isset($_SERVER['REQUEST_URI'])) {
            $text = self::green('Запущен через: ' . PHP_SAPI);
        } else {
            $text = self::red("Запущен через: Веб-сервер, но DOCUMENT_ROOT и REQUEST_URI не удалось получить");
        }
        return [PHP_SAPI, $text];
    }

    private static function checkImportantModules() {
        self::module('curl');
        self::module('json');
        self::module('mbstring');
        self::module('pcntl');
        self::module('posix');
        self::module('fileinfo');
        self::module('iconv');
    }

    private static function checkNoImportantModules() {
        self::module('mysqli');
        self::module('pdo_mysql');
        self::module('sqlite3');
        self::module('pdo_sqlite');
        self::module('pgsql');
        self::module('pdo_pgsql');
    }

    private static function module($name) {
        if (extension_loaded($name)) {
            self::$final_text .= self::green($name, ', ', '');
        } else {
            self::$final_text .= self::red($name, ', ', '');
        }
    }
}

Diagnostics::run();