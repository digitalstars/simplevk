<?php
namespace DigitalStar\vk_api;

class Execute extends vk_api {
    private $vk;
    private $counter = 0;
    static private $max_counter = 10; // 25;
    private $messages = [];
    private $constructors_messages = [];

    public function __construct($vk) {
        parent::setAllDataclass($vk->copyAllDataclass());
        $this->vk = $vk;
    }

    public function __destruct() {
        $this->exec();
    }
	
	/**
     * @param null $id
     * @param null $message
     * @param null $payload
     * @param null $user_id
     * @param null $type
     * @param null $data
     * @return array|mixed|null
     */
    public function initVars(&$id = null, &$message = null, &$payload = null, &$user_id = null, &$type = null, &$data = null) {
        if (!$this->vk->debug_mode)
            $this->vk->sendOK();
        $data = $this->vk->data;
        $data_backup = $this->vk->data;
        $type = isset($data->type) ? $data->type : null;
        if($type == 'message_new' && isset($data->object->message)) {
            $data->object = $data->object->message;
        }
        $id = isset($data->object->peer_id) ? $data->object->peer_id : null;
        $message = isset($data->object->text) ? $data->object->text : null;
        $payload = isset($data->object->payload) ? json_decode($data->object->payload, true) : null;
        $user_id = isset($data->object->from_id) ? $data->object->from_id : null;
        $data = $data_backup;
        return $data_backup;
    }

    public function sendMessage($id, $message, $props = []) {
        $message = $this->vk->placeholders($id, $message);
        $this->messages[] = ['peer_id' => $id, 'message' => $message, "random_id" => rand(-2147483648, 2147483647)] + $props;
        $this->counter += 1;
        $this->checkExec();
    }

    public function sendButton($id, $message, $buttons = [], $inline = false, $one_time = False, $params = []) {
        $keyboard = $this->generateKeyboard($buttons, $inline, $one_time);
        $message = $this->vk->placeholders($id, $message);
        $this->messages[] = ['message' => $message, 'peer_id' => $id, 'keyboard' => $keyboard, "random_id" => rand(-2147483648, 2147483647)] + $params;
        $this->counter += 1;
        $this->checkExec();
    }

    public function reply($message, $params = []) {
        if ($this->vk->data != []) {
            return $this->sendMessage($this->vk->data->object->peer_id, $message, $params);
        } else {
            throw new VkApiException('Вк не прислал callback, возможно вы пытаетесь запустить скрипт с локалки');
        }
    }

    private function generateUrlPhotos($id, $count) {
        $code = [];
        for ($i = 0; $i < $count; ++$i)
            $code[] = "API.photos.getMessagesUploadServer({\"peer_id\" : $id})";
        return $this->request("execute", ["code" => "return [".join(',', $code). "];"]);
    }

    private function generateUrlDocs($id, $count) {
        $code = [];
        for ($i = 0; $i < $count; ++$i)
            $code[] = "API.docs.getMessagesUploadServer({\"peer_id\" : $id, \"type\": \"doc\"})";
        return $this->request("execute", ["code" => "return [".join(',', $code). "];"]);
    }

    public function createMessages($id, $message = [], $props = [], $media = [], $keyboard = []) {

        if (!isset($media['images']))
            $media['images'] = [];
        if (!isset($media['docs']))
            $media['docs'] = [];

        if (count($media['docs']) + count($media['images']) + 1 + $this->counter > Execute::$max_counter)
            $this->exec();

        if (count($media['images']) != 0)
            $photo_urls = $this->generateUrlPhotos($id, count($media['images']));

        if (count($media['docs']) != 0)
            $doc_urls = $this->generateUrlDocs($id, count($media['docs']));

        if ($keyboard != [])
            $object = [
                'id' => $id,
                'message' => $message,
                'keyboard_content' => $this->generateKeyboard($keyboard['keyboard'], $keyboard['inline'], $keyboard['one_time']),
                'images_content' => [],
                'docs_content' => []
            ];
        else
            $object = [
                'id' => $id,
                'message' => $message,
                'keyboard_content' => [],
                'images_content' => [],
                'docs_content' => []
            ];

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
                    foreach ($massiv as $key => $document) {
                        for ($i = 0; $i < $this->try_count_resend_file; ++$i) {
                            try {
                                $title = isset($document['title']) ? $document['title'] : preg_replace("!.*?/!", '', $document);
                                $answer_vk = json_decode($this->sendFiles($doc_urls[$key]['upload_url'], $document['path']), true);
                                $object['docs_content'][] = ['file' => $answer_vk['file'], 'title' => $title];
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
                case "other":
                    break;
            }
        }
        $this->counter += 1;
        $this->constructors_messages[] = $object;
        $this->checkExec();
        return true;
    }

    private function getConversationsExec($offset) {
        $code = 'var count = API.messages.getConversations({"count": 200, "offset": 0})["count"];
var start_offset = '. $offset .';
var temp_count = 0;
var count_apis = 1;
var result = [];
var write_allowed = [];
var ids = [];
while ((temp_count + start_offset) < count && count_apis < 25) {
	result = API.messages.getConversations({"count": 200, "offset": (temp_count + start_offset)})["items"]@.conversation;
	write_allowed = write_allowed + result@.can_write@.allowed;
	ids = ids + result@.peer@.id;
	temp_count = temp_count + 200;
	count_apis = count_apis + 1;
}
return {"count": count, "offset_ok": (temp_count + start_offset),"write_allowed": write_allowed, "ids": ids};';
//        $code = 'return API.messages.getConversations({"count": 200, "offset": 0})["count"];';
        return $this->request("execute", ["code" => $code]);
    }

    private function getConversationsIds() {
        $ids = [];

        $exec_result = [
            "count" => 1,
            "offset_ok" => 0
        ];
        while ($exec_result['count'] > $exec_result['offset_ok']) {
            $exec_result = $this->getConversationsExec($exec_result['offset_ok']);
            echo "{$exec_result['offset_ok']} / {$exec_result['count']}, ";
            foreach ($exec_result['write_allowed'] as $key => $var)
                if ($var)
                    $ids [] = $exec_result['ids'][$key];
        }
        $count = count($ids);
        $ids = array_unique($ids);
        echo "Complete!\nВсего id:  ".$count."\nДубликатов: ".($count - count($ids))."\n";
        return $ids;
    }

//     public function sendAllDialogs($message) {
//         $ids = $this->getConversationsIds();
//         $ids = array_chunk($ids, 100);
//         foreach ($ids as $ids_chunk) {
//             $this->messages[] = ['user_ids' => join(',', $ids_chunk), 'message' => $message, "random_id" => rand(-2147483648, 2147483647)];
//             $this->counter += 1;
//             $this->checkExec();
//         }
//         echo "COUNT = ".$this->counter."\n";
//     }

    private function checkExec() {
        if ($this->counter >= Execute::$max_counter)
            return $this->exec();
        return false;
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

var data_result = [];

while (query[count] != null) {
	text_attach_photo = "";
	resulter = [];
	count_image = 0;
	while (query[count]["images_content"][count_image] != null) {
		resulter = API.photos.saveMessagesPhoto(query[count]["images_content"][count_image]);
		if (text_attach_photo == "") {
			text_attach_photo = "photo" + resulter[0]["owner_id"] + "_" + resulter[0]["id"];
		} else {
			text_attach_photo = text_attach_photo + ",photo" + resulter[0]["owner_id"] + "_" + resulter[0]["id"];
		}
		count_image = count_image + 1;
	}
	count_image = 0;
	while (query[count]["docs_content"][count_image] != null) {
		resulter = API.docs.save(query[count]["docs_content"][count_image]);
		if (text_attach_photo == "") {
			text_attach_photo = "doc" + resulter["doc"]["owner_id"] + "_" + resulter["doc"]["id"];
		} else {
			text_attach_photo = text_attach_photo + ",doc" + resulter["doc"]["owner_id"] + "_" + resulter["doc"]["id"];
		}
		count_image = count_image + 1;
	}
	data_result.push(API.messages.send({"peer_id": query[count]["id"], "message": query[count]["message"], "random_id": 0, "attachment": text_attach_photo, "keyboard": query[count]["keyboard_content"]}));
	count = count + 1;
}

count = 0;
while (query_message[count] != null) {
	data_result.push(API.messages.send(query_message[count]));
	count = count + 1;
}

return data_result;';
        $this->messages = [];
        $this->constructors_messages = [];
        return $this->request("execute", ["code" => $code]);
    }
}
