<?php


namespace DigitalStars\simplevk;


class Post extends BaseConstructor {
    public static function create($vk = null, &$cfg = null) {
        return new self($vk, $cfg);
    }

    public function send($id = null, $publish_date = null, $vk = null) {
        $params = [];
        if (!is_null($publish_date)) {
            if ($publish_date >= time())
                $params['publish_date'] = $publish_date;
            else
                throw new VkApiException('Неверно указан $publish_date');
        }
        if (empty($this->vk) and isset($vk))
            $this->vk = $vk;
        if (empty($this->vk))
            throw new SimpleVkException(0, "Экземпляр SimpleVK не передан");
        if (empty($id)) {
            $id = $this->vk->userInfo()['id'];
        }
        $this->config_cache = $this->config;
        if ($this->preProcessing($id, null))
            return null;

        $attachments = [];
        if (isset($this->config['img']))
            foreach ($this->config['img'] as $img)
                $attachments[] = $this->getWallAttachmentUploadImage($id, $img[0]);
        if (isset($this->config['doc']))
            foreach ($this->config['doc'] as $doc)
                $attachments[] = $this->getWallAttachmentUploadDoc($id, $doc[0], $doc[1]);
        if (isset($this->config['attachments']))
            $attachments = array_merge($attachments, $this->config['attachments']);
        if (isset($this->config['params']['attachment'])) {
            $attachments = array_merge($attachments, $this->config['params']['attachment']);
            unset($this->config['params']['attachment']);
        }
        $attachments = !empty($attachments) ? ['attachment' => join(",", $attachments)] : [];

        if (isset($this->config['params']))
            $params += $this->config['params'];
        $text = isset($this->config['text']) ? ['message' => $this->config['text']] : [];
        $query = $text + $params + $attachments;
        if (empty($query))
            $result = null;
        else
            $result = $this->request('wall.post', ['owner_id' => $id] + $query);
        $this->postProcessing($result, null);
        return $result;
    }
}