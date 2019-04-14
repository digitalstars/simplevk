<?php
namespace DigitalStar\vk_api;

class LongPoll extends vk_api
{
    private $vk;
    private $group_id;
    private $key;
    private $server;
    private $ts;

    public function __construct($vk) {
        $this->vk = $vk;
        $this->group_id = $this->vk->request('groups.getById', [])[0]['id'];
        parent::setAllDataclass($vk->copyAllDataclass());
        $this->vk->request('groups.setLongPollSettings', [
            'group_id' => $this->group_id,
            'enabled' => 1,
            'api_version' => $this->vk->version,
            'message_new' => 1
        ]);

        $this->getLongPollServer();
    }

    protected function getLongPollServer() {
       $data = $this->vk->request('groups.getLongPollServer', ['group_id' => $this->group_id]);
       list($this->key, $this->server, $this->ts) = [$data['key'], $data['server'], $data['ts']];
    }

    public function getData() {
        $data =  json_decode(file_get_contents("{$this->server}?act=a_check&key={$this->key}&ts={$this->ts}&wait=25"));
        if(isset($data->failed)) {
            if($data->failed == 1)
                $this->ts = $data->ts;
            else {
                $this->getLongPollServer();
                $data =  json_decode(file_get_contents("{$this->server}?act=a_check&key={$this->key}&ts={$this->ts}&wait=25"));
            }
        }
        $this->ts = $data->ts;
        return $data;
    }

    public function listen($anon) {
        while($data = $this->getData())
            foreach ($data->updates as $event) {
                $this->data = $event;
                $anon($event);
            }
    }

    public function initVars($selectors, &...$args)
    {
        $data = $this->data;

        if (isset($data->object->payload))
            $data->object->payload = json_decode($data->object->payload, true);

        $init = [
            'id' => $data->object->peer_id ?? null,
            'user_id' => $data->object->from_id ?? null,
            'message' => $data->object->text ?? null,
            'payload' => $data->object->payload ?? null,
            'type' => $this->data->type ?? null,
            'all' => $data,
        ];
        $selectors = explode(',', $selectors);
        if (count($selectors) != count($args))
            throw new VkApiException('Разное количество аргументов и переменных при инициализации');
        foreach ($selectors as $key => $val)
            $args[$key] = $init[trim($val)];
    }
}