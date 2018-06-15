<?php
class vk_api{
	/**
	 * Токен
	 * @var string
	 */
	private $token = '';
	/**
	 * @param string $token Токен
	 */
	public function __construct($token){
		$this->token = $token;
	}
	/**
	 * Отправить сообщение пользователю
	 * @param int $userID Идентификатор пользователя
	 * @param string $message Сообщение
	 * @return mixed|null
	 */
	public function sendDocMessage($userID,$id_owner,$id_doc){
		if ($userID != 0 and $userID != '0') {
			return $this->request('messages.send',array('attachment'=>"doc". $id_owner . "_" . $id_doc,'user_id'=>$userID));
		} else {
			return true;
		}
	}

	public function sendMessage($userID,$message){
		if ($userID != 0 and $userID != '0') {
			return $this->request('messages.send',array('message'=>$message,'user_id'=>$userID));
		} else {
			return true;
		}
	}

	public function sendButton($userID, $message, $gl_massiv) {
		$buttons = [];
		$i = 0;
		foreach ($gl_massiv as $button_str) {
			$j = 0;
			foreach ($button_str as $button) {
				$color = $this->replaceColor($button[2]);
				$buttons[$i][$j]["action"]["type"] = "text";
				if ($button[0] != null)
					$buttons[$i][$j]["action"]["payload"] = json_encode($button[0]);
				$buttons[$i][$j]["action"]["label"] = $button[1];
				$buttons[$i][$j]["color"] = $color;
				$j++;
			}
			$i++;	
		}
		$buttons = array(
			"one_time" => False,
			"buttons" => $buttons);
		$buttons = json_encode($buttons);
		//echo $buttons;
		return $this->request('messages.send',array('message'=>$message, 'user_id'=>$userID, 'keyboard'=>$buttons));
	}

	public function sendDocuments($userID, $selector = 'doc'){
		if ($selector == 'doc')
			return $this->request('docs.getMessagesUploadServer',array('type'=>'doc','peer_id'=>$userID));
		else
			return $this->request('photos.getMessagesUploadServer',array('peer_id'=>$userID));
	}

	public function saveDocuments($file, $titile){
		return $this->request('docs.save',array('file'=>$file, 'title'=>$titile));
	}

	public function savePhoto($photo, $server, $hash){
		return $this->request('photos.saveMessagesPhoto',array('photo'=>$photo, 'server'=>$server, 'hash' => $hash));
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
		$params['v']='5.78';
		return json_decode(file_get_contents($url.'?'.http_build_query($params)), true);
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
	  return $output;
	}

	public function sendImage($id, $local_file_path, $filename, $message = '') {
	  $upload_url = $this->sendDocuments($id, 'photo')['response']['upload_url'];

	  $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path, 'photo'), true);

	  $upload_file = $this->savePhoto($answer_vk['photo'], $answer_vk['server'], $answer_vk['hash']);

	  $this->request('messages.send',array('attachment'=>"photo". $upload_file['response'][0]['owner_id'] . "_" . $upload_file['response'][0]['id'],'user_id'=>$id));
	  
	  return 1;
	}
}
