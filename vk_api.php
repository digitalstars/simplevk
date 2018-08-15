<?php
class vk_api{
    /**
     * Токен
     * @var string
     */
    private $token = '';
    private $v = '';
    /**
     * @param string $token Токен
     */
    public function __construct($token, $v){
        $this->token = $token;
        $this->v = $v;
    }
    /**
     * Отправить сообщение пользователю
     * @param int $sendID Идентификатор получателя
     * @param string $message Сообщение
     * @return mixed|null
     */
    public function sendDocMessage($sendID, $id_owner, $id_doc){
        if ($sendID != 0 and $sendID != '0') {
            return $this->request('messages.send',array('attachment'=>"doc". $id_owner . "_" . $id_doc,'user_id'=>$sendID));
        } else {
            return true;
        }
    }

    public function sendMessage($sendID,$message){
        if ($sendID != 0 and $sendID != '0') {
            return $this->request('messages.send',array('message'=>$message, 'peer_id'=>$sendID));
        } else {
            return true;
        }
    }

    public function sendOK(){
        echo 'ok';
        $response_length = ob_get_length();
        // check if fastcgi_finish_request is callable
        if (is_callable('fastcgi_finish_request')) {
            /*
             * This works in Nginx but the next approach not
             */
            session_write_close();
            fastcgi_finish_request();

            return;
        }

        ignore_user_abort(true);

        ob_start();
        $serverProtocole = filter_input(INPUT_SERVER, 'SERVER_PROTOCOL', FILTER_SANITIZE_STRING);
        header($serverProtocole.' 200 OK');
        header('Content-Encoding: none');
        header('Content-Length: '. $response_length);
        header('Connection: close');

        ob_end_flush();
        ob_flush();
        flush();
    }

    private function generateButton($gl_massiv = [], $one_time = False) {
        $buttons = [];
        $i = 0;
        foreach ($gl_massiv as $button_str) {
            $j = 0;
            foreach ($button_str as $button) {
                $color = $this->replaceColor($button[2]);
                $buttons[$i][$j]["action"]["type"] = "text";
                if ($button[0] != null)
                    $buttons[$i][$j]["action"]["payload"] = json_encode($button[0], JSON_UNESCAPED_UNICODE);
                $buttons[$i][$j]["action"]["label"] = $button[1];
                $buttons[$i][$j]["color"] = $color;
                $j++;
            }
            $i++;
        }
        $buttons = array(
            "one_time" => $one_time,
            "buttons" => $buttons);
        $buttons = json_encode($buttons, JSON_UNESCAPED_UNICODE);
        return $buttons;
    }

    public function sendButton($sendID, $message, $gl_massiv = [], $one_time = False) {
        $buttons = $this->generateButton($gl_massiv, $one_time);
        return $this->request('messages.send',array('message'=>$message, 'peer_id'=>$sendID, 'keyboard'=>$buttons));
    }

    public function getUploadServer($sendID, $selector = 'doc'){
        $result = null;
        if ($selector == 'doc')
            $result = $this->request('docs.getMessagesUploadServer',array('type'=>'doc','peer_id'=>$sendID));
        else if ($selector == 'photo')
            $result = $this->request('photos.getMessagesUploadServer',array('peer_id'=>$sendID));
        return $result;
    }

    public function getWallUploadServer($id) {
        if ($id < 0) {
            $id *= -1;
            return $this->request('photos.getWallUploadServer', array('group_id' => $id));
        } else {
            return $this->request('photos.getWallUploadServer', array('user_id' => $id));
        }
    }

    public function saveDocuments($file, $titile){
        return $this->request('docs.save',array('file'=>$file, 'title'=>$titile));
    }

    public function savePhoto($photo, $server, $hash){
        return $this->request('photos.saveMessagesPhoto',array('photo'=>$photo, 'server'=>$server, 'hash' => $hash));
    }

    public function savePhotoWall($photo, $server, $hash, $id){
        if ($id < 0) {
            $id *= -1;
            return $this->request('photos.saveWallPhoto', array('photo' => $photo, 'server' => $server, 'hash' => $hash, 'group_id' => $id));
        } else {
            return $this->request('photos.saveWallPhoto', array('photo' => $photo, 'server' => $server, 'hash' => $hash, 'user_id' => $id));
        }
    }

    /**
     * Запрос к VK
     * @param string $method Метод
     * @param array $params Параметры
     * @return mixed|null
     */
    public function request($method,$params=array()){
        $url = 'https://api.vk.com/method/'.$method;
        $params['access_token']=$this->token;
        $params['v']=$this->v;
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type:multipart/form-data"
            ));
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            $result = json_decode(curl_exec($ch), True);
            curl_close($ch);
        } else {
            $result = json_decode(file_get_contents($url, true, stream_context_create(array(
                'http' => array(
                    'method'  => 'POST',
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'content' => http_build_query($params)
                )
            ))), true);
        }
        if (!isset($result) or isset($result['error']))
            throw new vk_apiException(json_encode($result));
        if (isset($result['response']))
            return $result['response'];
        else
            return $result;
    }

    private function replaceColor($color) {
        switch ($color) {
            case 'red':
                $color = 'negative';
                break;
            case 'green':
                $color = 'positive';
                break;
            case 'white':
                $color = 'default';
                break;
            case 'blue':
                $color = 'primary';
                break;

            default:
                # code...
                break;
        }
        return $color;
    }

    private function sendFiles($url, $local_file_path, $type = 'file') {
        $post_fields = array(
            $type => new CURLFile(realpath($local_file_path))
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type:multipart/form-data"
        ));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        $output = curl_exec($ch);
        if ($output == '')
            throw new vk_apiException('Не удалось загрузить файл на сервер');
        return $output;
    }

    private function uploadImage($id, $local_file_path) {
        $upload_url = $this->getUploadServer($id, 'photo')['upload_url'];
        $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path, 'photo'), true);
        $upload_file = $this->savePhoto($answer_vk['photo'], $answer_vk['server'], $answer_vk['hash']);
        return $upload_file;
    }

    public function sendImage($id, $local_file_path) {
        $upload_file = $this->uploadImage($id, $local_file_path);
        return $this->request('messages.send', array('attachment' => "photo" . $upload_file[0]['owner_id'] . "_" . $upload_file[0]['id'], 'peer_id' => $id));
    }

    public function createPost($id, $message = null, $other = null) {
        $send_attachment = [];
        $send_other = [];
        $send_message = [];
        if (isset($other['images']) and count($other['images']) != 0) {
            foreach ($other['images'] as $kay => $val) {
                $upload_url = $this->getWallUploadServer($id);
                $answer_vk = json_decode($this->sendFiles($upload_url['upload_url'], $val, 'photo'), true);
                $upload_file = $this->savePhotoWall($answer_vk['photo'], $answer_vk['server'], $answer_vk['hash'], $id);
                $send_attachment[] = "photo" . $upload_file[0]['owner_id'] . "_" . $upload_file[0]['id'];
            }
            $send_attachment = ["attachments" => join(',', $send_attachment)];
        }
        if (isset($other['props'])) {
            $send_other = $other['props'];
        }
        if (isset($message))
            $send_message = ['message' => $message];
        return $this->request('wall.post', ['owner_id' => $id] + $send_message + $send_other + $send_attachment);
        // return ['owner_id' => $id] + $send_message + $send_other + $send_attachment;
    }

    public function createMessages($id, $message = null, $keyboard = null, $other = null) {
        $buttons = [];
        $send_attachment = [];
        $send_other = [];
        $send_message = [];
        if (isset($other['images']) and count($other['images']) != 0) {
            foreach ($other['images'] as $kay => $val) {
                $upload_file = $upload_file = $this->uploadImage($id, $val);
                $send_attachment[] = "photo" . $upload_file[0]['owner_id'] . "_" . $upload_file[0]['id'];
            }
            $send_attachment = ["attachment" => join(',', $send_attachment)];
        }
        if (isset($other['props'])) {
            $send_other = $other['props'];
        }
        if (isset($message))
            $send_message = ['message' => $message];
        if (isset($keyboard))
            $buttons = ['keyboard' => $this->generateButton($keyboard['keyboard'], $keyboard['one_time'])];
        return $this->request('messages.send', ['peer_id' => $id] + $send_message + $send_other + $send_attachment + $buttons);
        // return ['peer_id' => $id] + $send_message + $send_other + $send_attachment + $buttons;
    }
}

class base {
    protected $vk_api;
    protected $message = null;
    protected $media = [];
    protected $props = [];

    protected $prop_list;

    protected function __construct($vk_api)
    {
        $this->vk_api = $vk_api;
    }

    private function countMedia() {
        $count = 0;
        foreach ($this->media as $kye => $var) {
            $count += count($var);
        }
        return $count;
    }

    public function setMessage($message) {
        $this->message = $message;
    }

    protected function addMedia($media, $selector) {
        if (is_array($media))
            foreach ($media as $kay => $val) {
                if (is_array($val))
                    $this->media[$selector] += $val;
                else
                    $this->media[$selector][] = $val;
            }
        else
            $this->$selector[] = $media;
        if ($this->countMedia() > 10)
            throw new vk_apiException('Максимум 10 прикрепляемых файлов');
    }

    public function getImages() {
        if (isset($this->media['images']))
            return $this->media['images'];
    }

    public function getMessage() {
        return $this->message;
    }

    public function removeImages($images) {
        $search = array_search($images, $this->media['images']);
        if ($search) {
            $remove_val = $this->media['images'][$search];
            unset($this->media['images'][$search]);
            return $remove_val;
        }
        if (is_numeric($images) and ($images >= 0 and $images <= count($this->media['images']) -1)) {
            $remove_val = $this->media['images'][$images];
            unset($this->media['images'][$images]);
            return $remove_val;
        }
        return 0;
    }

    public function addProp($prop, $value) {
        if (!in_array($prop, $this->prop_list))
            return 0;
        $this->props += [$prop => $value];
        return $prop;
    }

    public function removeProp($prop) {
        $search = array_search($prop, $this->props);
        if ($search) {
            $remove_val = $this->props[$search];
            unset($this->props[$search]);
            return $remove_val;
        }
        if (is_numeric($prop) and ($prop >= 0 and $prop <= count($this->props) -1)) {
            $remove_val = $this->props[$prop];
            unset($this->props[$prop]);
            return $remove_val;
        }
        return 0;
    }

    public function getProps() {
        return $this->props;
    }

    public function addImage() {
        $this->addMedia(func_get_args(), 'images');
    }
}


class post extends base{

    public function __construct($vk_api) {
        $this->prop_list = ['friends_only', 'from_group', 'services', 'signed', 'publish_date', 'lat', 'long', 'place_id',
            'post_id', 'guid', 'mark_as_ads', 'close_comments'];
        parent::__construct($vk_api);
    }

    public function send($id, $publish_date = null) {
        if (is_numeric($publish_date) or $publish_date <= time())
            $this->props['publish_date'] = $publish_date;
        else
            throw new vk_apiException('Неверно указан $publish_date');
        $other = $this->media + ['props' => $this->props];
        return $this->vk_api->createPost($id, $this->message, $other);
    }
}

class message extends base{

    private $keyboard = null;

    public function __construct($vk_api) {
        $this->prop_list = ['random_id', 'domain', 'chat_id', 'user_ids', 'lat', 'long', 'forward_messages',
            'sticker_id', 'payload'];
        parent::__construct($vk_api);
    }

    public function setKayboards($keyboard = [], $one_time = false) {
        $this->keyboard = ['keyboard' => $keyboard, 'one_time' => $one_time];
        echo "Test";
    }

    public function getKeyboard() {
        return $this->keyboard;
    }

    public function send($id) {
        $other = $this->media + ['props' => $this->props];
        return $this->vk_api->createMessages($id, $this->message, $this->keyboard, $other);
    }
}


class vk_apiException extends Exception {
    function __construct($message) {
        parent::__construct($message);
    }
}