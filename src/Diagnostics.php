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
            if (isset($_GET['type']) && $_GET['type'] == 'check_send_ok')
                exit(self::sendOK());
            if (isset($_GET['type']) && $_GET['type'] == 'check_headers')
                exit((isset($_SERVER['HTTP_RETRY_AFTER']) && $_SERVER['HTTP_RETRY_AFTER'] == 'test_1' &&
                        isset($_SERVER['HTTP_X_RETRY_COUNTER']) && $_SERVER['HTTP_X_RETRY_COUNTER'] == 'test_2')
                    ? 'ok' : 'no');
            self::$final_text .= '<html><body style="background-color: black">' .
                '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>';
        }

        self::$final_text .= self::cyan("Диагностика системы для работы с SimpleVK " . SIMPLEVK_VERSION, $EOL, '') . $EOL;
        self::$final_text .= self::cyan("Информация о системе", $EOL, '');

        if (PHP_MAJOR_VERSION >= 7)
            self::$final_text .= self::green("PHP: " . PHP_VERSION);
        else
            self::$final_text .= self::red("PHP: " . PHP_VERSION);

        self::$final_text .= self::webServerOrCli();

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

        if (PHP_SAPI != 'cli') {
            self::$final_text .= $EOL . self::cyan("Проверка sendOK и получение кастомных заголовков", $EOL, '') .
                '<span id="test_send_ok" style="color: white">· Выполняется фоновая проверка...</span><br>' .
                '<span id="test_check_header" style="color: white">· Выполняется фоновая проверка...</span><br>' .
                $EOL . self::yellow("Не забудьте удалить скрипт, чтобы другие не смогли узнать информацию о вашем сервере", $EOL, '') .
                '<script type="text/javascript">
    $.ajax({
      url: window.location.href,
      data: "type=check_send_ok",
      success: function (response) {
        let test_send_ok = $("#test_send_ok");
        if (response == "ok") {
          test_send_ok.text("· sendOK работает");
          test_send_ok.css("color", "green");
        } else {
          test_send_ok.text("· sendOK не работает на вашем веб сервере");
          test_send_ok.css("color", "red");
        }
      }
    });
  $.ajax({
      url: window.location.href,
      data: "type=check_headers",
      headers: {"Retry-After": "test_1", "X-Retry-Counter": "test_2"},
      success: function (response) {
        let test_headers = $("#test_check_header");
        if (response == "ok") {
          test_headers.text("· php получает кастомные заголовки");
          test_headers.css("color", "green");
        } else {
          test_headers.text("· php не получает кастомные заголовки на вашем веб сервере");
          test_headers.css("color", "red");
        }
      }
    });
</script>
</body></html>';
        }

        print self::$final_text;
    }

    private static function sendOK() {
        echo 'Test?';
        set_time_limit(0);
        ini_set('display_errors', 'Off');
        ob_end_clean();

        // для Nginx
        if (is_callable('fastcgi_finish_request')) {
            echo 'ok';
            session_write_close();
            fastcgi_finish_request();
            return True;
        }
        // для Apache
        ignore_user_abort(true);

        PHP_EOL;
        ob_start();
        header('Content-Encoding: none');
        header('Content-Length: 2');
        header('Connection: close');
        echo 'ok';
        ob_end_flush();
        flush();
        return True;
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
        return $text;
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