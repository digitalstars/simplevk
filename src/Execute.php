
        foreach ($media as $selector => $massiv) {
            switch ($selector) {
                case "images":
                    foreach ($massiv as $key => $image) {
                        for ($i = 0; $i < $this->try_count_resend_file; ++$i) {
                            try {
                                $answer_vk = json_decode($this->sendFiles($photo_urls[$key]['upload_url'], $image, 'photo'), true);
                                $object['images_content'][] = ['photo' => $answer_vk['photo'], 'server' => $answer_vk['server'], 'hash' => $answer_vk['hash']];
                                $this->counter += 1;
                                break;
                            } catch (VkApiException $e) {
                                sleep(1);
                                $exception = json_decode($e->getMessage(), true);
                                if ($exception['error']['error_code'] != 121)
                                    throw new VkApiException($e->getMessage());
                            }
                        }
                    }
                    break;
                case "docs":
//                    foreach ($massiv as $document) {
//                        $upload_file = $this->uploadDocsMessages($id, $document['path'], $document['title']);
//                        if (isset($upload_file['type']))
//                            $upload_file = $upload_file[$upload_file['type']];
//                        else
//                            $upload_file = current($upload_file);
//                        $send_attachment[] = "doc" . $upload_file['owner_id'] . "_" . $upload_file['id'];
//                    }
                    break;
                case "other":
                    break;
            }
        }
        $this->counter += 1;
        $this->constructors_messages[] = $object;
        return true;
    }

    private function checkExec() {
        if ($this->counter >= Execute::$max_counter)
            $this->exec();
    }

    public function execTest() {
        echo json_encode($this->constructors_messages, JSON_UNESCAPED_UNICODE);
    }

    public function starter($code) {
        return $this->request("execute", ["code" => $code]);
    }

    public function exec() {
        if ($this->counter == 0)
            return false;
        $this->counter = 0;
        $code = 'var query = '. json_encode($this->constructors_messages, JSON_UNESCAPED_UNICODE) .';
var query_message = '. json_encode($this->messages, JSON_UNESCAPED_UNICODE) .';

var count = 0;
var count_image = 0;
var text_attach_photo = "";
var resulter = [];

while (query[count] != null) {
	text_attach_photo = "";
	resulter = [];
	count_image = 0;
	while (query[count]["images_content"][count_image] != null) {
		resulter = API.photos.saveMessagesPhoto({"photo": query[count]["images_content"][count_image]["photo"], "server": query[count]["images_content"][count_image]["server"], "hash": query[count]["images_content"][count_image]["hash"]});
		if (text_attach_photo == "") {
			text_attach_photo = "photo" + resulter[0]["owner_id"] + "_" + resulter[0]["id"];
		} else {
			text_attach_photo = text_attach_photo + ",photo" + resulter[0]["owner_id"] + "_" + resulter[0]["id"];
		}
		count_image = count_image + 1;
	}
	API.messages.send({"peer_id": query[count]["id"], "message": query[count]["message"], "random_id": 0, "attachment": text_attach_photo, "keyboard": query[count]["keyboard_content"]});
	count = count + 1;
}

count = 0;
while (query_message[count] != null) {
	API.messages.send({"peer_id": query_message[count]["id"], "message": query_message[count]["message"], "random_id": 0});
	count = count + 1;
}

return 1;';
        $this->messages = [];
        $this->constructors_messages = [];
        return $this->request("execute", ["code" => $code]);
    }
}