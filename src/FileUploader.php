<?php


namespace DigitalStars\simplevk;

use CURLFile;

require_once('config_simplevk.php');

trait FileUploader {
    protected $try_count_resend_file = COUNT_TRY_SEND_FILE;

    protected function getUploadServerMessages($peer_id, $selector = 'doc') {
        if ($selector == 'doc')
            return $this->request('docs.getMessagesUploadServer', ['type' => 'doc', 'peer_id' => $peer_id]);
        else if ($selector == 'photo')
            return $this->request('photos.getMessagesUploadServer', ['peer_id' => $peer_id]);
        else if ($selector == 'audio_message')
            return $this->request('docs.getMessagesUploadServer', ['type' => 'audio_message', 'peer_id' => $peer_id]);
        return null;
    }

    protected function sendFiles($url, $local_file_path, $type = 'file') {
        if (filter_var($local_file_path, FILTER_VALIDATE_URL) === false)
            $post_fields = [
                $type => new CURLFile(realpath($local_file_path))
            ];
        else {
            $tmp_file = tmpfile();
            $tmp_filename = stream_get_meta_data($tmp_file)['uri'];
            if (!copy($local_file_path, $tmp_filename)) {
                fclose($tmp_file);
                throw new SimpleVkException(0, "Ошибка скачивания файла");
            }
            $mime_type = mime_content_type($tmp_filename);
            $post_fields = [
                $type => new CURLFile($tmp_filename, '$mime_type', 'file.'.explode('/', $mime_type)[1])
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
            throw new SimpleVkException(0,'Не удалось загрузить файл на сервер');
        return $output;
    }

    private function savePhoto($photo, $server, $hash) {
        $upload_file = $this->request('photos.saveMessagesPhoto', ['photo' => $photo, 'server' => $server, 'hash' => $hash]);
        return "photo" . $upload_file[0]['owner_id'] . "_" . $upload_file[0]['id'];
    }

    protected function uploadImage($id, $local_file_path) {
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
        return $this->request('docs.save', ['file' => $file, 'title' => $title]);
    }

    protected function uploadDocsMessages($id, $local_file_path, $title = null) {
        if (!isset($title))
            $title = preg_replace("!.*?/!", '', $local_file_path);
        $upload_url = $this->getUploadServerMessages($id)['upload_url'];
        $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path), true);
        $upload_file = $this->saveDocuments($answer_vk['file'], $title);
        if (isset($upload_file['type']))
            $upload_file = $upload_file[$upload_file['type']];
        else
            $upload_file = current($upload_file);
        return "doc" . $upload_file['owner_id'] . "_" . $upload_file['id'];
    }

    protected function uploadVoice($id, $local_file_path) {
        $upload_url = $this->getUploadServerMessages($id, 'audio_message')['upload_url'];
        $answer_vk = json_decode($this->sendFiles($upload_url, $local_file_path, 'file'), true);
        $upload_file = $this->saveDocuments($answer_vk['file'], 'voice');
        return "doc" . $upload_file['audio_message']['owner_id'] . "_" . $upload_file['audio_message']['id'];
    }
}