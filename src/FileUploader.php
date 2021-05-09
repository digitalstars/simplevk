<?php

namespace DigitalStars\SimpleVK;

use CURLFile;

require_once('config_simplevk.php');

trait FileUploader {
    protected $try_count_resend_file = COUNT_TRY_SEND_FILE;

    private function getUploadServerMessages($peer_id, $selector = 'doc') {
        if ($selector == 'doc')
            return $this->request('docs.getMessagesUploadServer', ['type' => 'doc', 'peer_id' => $peer_id]);
        else if ($selector == 'photo')
            return $this->request('photos.getMessagesUploadServer', ['peer_id' => $peer_id]);
        else if ($selector == 'audio_message')
            return $this->request('docs.getMessagesUploadServer', ['type' => 'audio_message', 'peer_id' => $peer_id]);
        return null;
    }

    private function sendFiles($url, $local_file_path, $type = 'file') {
        if (filter_var($local_file_path, FILTER_VALIDATE_URL) === false) {
            $file = realpath($local_file_path);
            if (!is_readable($file)){
                throw new SimpleVkException(0, "Файл для загрузки не найден" . PHP_EOL . $file);
            }
            $post_fields = [
                $type => new CURLFile(realpath($local_file_path))
            ];
        } else {
            $tmp_file = tmpfile();
            $tmp_filename = stream_get_meta_data($tmp_file)['uri'];
            if (!copy($local_file_path, $tmp_filename)) {
                fclose($tmp_file);
                throw new SimpleVkException(0, "Ошибка скачивания файла");
            }
            $mime_type = mime_content_type($tmp_filename);
            $post_fields = [
                $type => new CURLFile($tmp_filename, '$mime_type', 'file.' . explode('/', $mime_type)[1])
            ];
        }

        for ($i = 0; $i < $this->try_count_resend_file; ++$i) {

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type:multipart/form-data"
            ]);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $output = curl_exec($ch);
            if ($output != '')
                break;
            else
                sleep(1);
        }
        if (isset($tmp_file))
            fclose($tmp_file);
        if ($output == '')
            throw new SimpleVkException(0, 'Не удалось загрузить файл на сервер');
        return $output;
    }

    private function savePhoto($photo, $server, $hash) {
        $upload_file = $this->request('photos.saveMessagesPhoto', ['photo' => $photo, 'server' => $server, 'hash' => $hash]);
        return "photo" . $upload_file[0]['owner_id'] . "_" . $upload_file[0]['id'];
    }

    public function getMsgAttachmentUploadImage($id, $local_file_path) {
        $upload_url = $this->getUploadServerMessages($id, 'photo')['upload_url'];
        for ($i = 0; $i < $this->try_count_resend_file; ++$i) {
            try {
                $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path, 'photo'), true);
                return $this->savePhoto($answer_vk['photo'], $answer_vk['server'], $answer_vk['hash']);
            } catch (SimpleVkException $e) {
                sleep(1);
                $exception = json_decode($e->getMessage(), true);
                if ($exception['error']['error_code'] != 121)
                    throw new SimpleVkException($exception['error']['error_code'], $e->getMessage());
            }
        }
        $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path, 'photo'), true);
        return $this->savePhoto($answer_vk['photo'], $answer_vk['server'], $answer_vk['hash']);
    }

    private function saveDocuments($file, $title) {
        $upload_file = $this->request('docs.save', ['file' => $file, 'title' => $title]);
        if (isset($upload_file['type']))
            $upload_file = $upload_file[$upload_file['type']];
        else
            $upload_file = current($upload_file);
        return "doc" . $upload_file['owner_id'] . "_" . $upload_file['id'];
    }

    public function getMsgAttachmentUploadDoc($id, $local_file_path, $title = null) {
        return $this->uploadDoc($this->getUploadServerMessages($id)['upload_url'], $local_file_path, $title);
    }

    private function uploadDoc(string $upload_url, string $local_file_path, $title = null)
    {
        !isset($title) ?: $title = preg_replace("!.*?/!", '', $local_file_path);
        for ($i = 0; $i < $this->try_count_resend_file; ++$i) {
            try {
                $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path), true);
                return $this->saveDocuments($answer_vk['file'], $title);
            } catch (SimpleVkException $e) {
                sleep(1);
                $exception = json_decode($e->getMessage(), true);
                if ($exception['error']['error_code'] != 121)
                    throw new SimpleVkException($exception['error']['error_code'], $e->getMessage());
            }
        }
        $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path), true);
        return $this->saveDocuments($answer_vk['file'], $title);
    }

    private function getUploadServerPost($peer_id = []) {
        if ($peer_id < 0)
            $peer_id = ['group_id' => $peer_id * -1];
        else
            $peer_id = [];
        $result = $this->request('docs.getUploadServer', $peer_id);
        return $result;
    }

    public function getWallAttachmentUploadDoc($id, $local_file_path, $title = null) {
        return $this->uploadDoc($this->getUploadServerPost($id)['upload_url'], $local_file_path, $title);
    }

    private function savePhotoWall($photo, $server, $hash, $id) {
        if ($id < 0) {
            $id *= -1;
            $upload_file = $this->request('photos.saveWallPhoto', ['photo' => $photo, 'server' => $server, 'hash' => $hash, 'group_id' => $id]);
        } else {
            $upload_file = $this->request('photos.saveWallPhoto', ['photo' => $photo, 'server' => $server, 'hash' => $hash, 'user_id' => $id]);
        }
        return "photo" . $upload_file[0]['owner_id'] . "_" . $upload_file[0]['id'];
    }

    private function getWallUploadServer($id) {
        if ($id < 0) {
            $id *= -1;
            return $this->request('photos.getWallUploadServer', ['group_id' => $id]);
        } else {
            return $this->request('photos.getWallUploadServer', ['user_id' => $id]);
        }
    }

    public function getWallAttachmentUploadImage($id, $local_file_path) {
        $upload_url = $this->getWallUploadServer($id)['upload_url'];
        for ($i = 0; $i < $this->try_count_resend_file; ++$i) {
            try {
                $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path, 'photo'), true);
                return $this->savePhotoWall($answer_vk['photo'], $answer_vk['server'], $answer_vk['hash'], $id);
            } catch (SimpleVkException $e) {
                sleep(1);
                $exception = json_decode($e->getMessage(), true);
                if ($exception['error']['error_code'] != 121)
                    throw new SimpleVkException($exception['error']['error_code'], $e->getMessage());
            }
        }
        $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path, 'photo'), true);
        return $this->savePhotoWall($answer_vk['photo'], $answer_vk['server'], $answer_vk['hash'], $id);
    }

    public function getMsgAttachmentUploadVoice($id, $local_file_path) {
        $upload_url = $this->getUploadServerMessages($id, 'audio_message')['upload_url'];
        for ($i = 0; $i < $this->try_count_resend_file; ++$i) {
            try {
                $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path, 'file'), true);
                return $this->saveDocuments($answer_vk['file'], 'voice');
            } catch (SimpleVkException $e) {
                sleep(1);
                $exception = json_decode($e->getMessage(), true);
                if ($exception['error']['error_code'] != 121)
                    throw new SimpleVkException($exception['error']['error_code'], $e->getMessage());
            }
        }
        $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path, 'file'), true);
        return $this->saveDocuments($answer_vk['file'], 'voice');
    }
}
