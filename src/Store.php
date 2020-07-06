<?php


namespace DigitalStars\simplevk;


class Store {
    public $data = null;
    public static $path = DIRNAME."/cache";
    private $file;
    private $full_path;

    public function __construct($filename = 0) {
        $this->full_path = self::$path."/".$filename.".php";
        if (!is_dir(self::$path))
            mkdir(self::$path);
        if (!file_exists($this->full_path))
            file_put_contents($this->full_path, '');

        $this->file = fopen( $this->full_path, 'r' );
        flock( $this->file, LOCK_SH );
        $line = '';
        while (!feof($this->file))
            $line = fgets($this->file);
        $this->data = json_decode($line, true);
        if (!is_array($this->data))
            $this->data = [];
    }

    public static function load($filename = 0) {
        return new self($filename);
    }

    public function save() {
        if (isset($this->data)) {
            json_encode($this->data);
            file_put_contents($this->full_path, "<?php http_response_code(404);exit('404');?>\n" . json_encode($this->data));
            flock($this->file, LOCK_UN);
            fclose($this->file);
            unset($this->data);
        }
    }

    public function __destruct() {
        $this->save();
    }

    public function get($key) {
        return $this->data[$key] ?? null;
    }

    public function set($key, $val) {
        $this->getWriteLock();
        $this->data[$key] = $val;
        return $this;
    }

    public function sset($key, $val) {
        $this->set($key, $val);
        $this->save();
    }

    public function getWriteLock() {
        flock( $this->file, LOCK_EX );
    }
}