<?php
/**
 * Created by PhpStorm.
 * User: zerox
 * Date: 25.08.18
 * Time: 23:59
 */

namespace DigitalStar\vk_api;


/**
 * Class Group
 * @package DigitalStar\vk_api
 */
class Group extends vk_api
{
    /**
     * @var
     */
    private $groupID;

    /**
     * Group constructor.
     * @param $groupID
     * @param $vk_api
     */
    public function __construct($groupID, $vk_api)
    {
        $this->groupID = $groupID;
        parent::setAllDataclass($vk_api->copyAllDataclass());
    }

    /**
     * @param $method
     * @param $params
     * @return array
     */
    protected function editRequestParams($method, $params)
    {
//        if ($method == 'messages.send' or $method == 'photos.saveMessagesPhoto')
        $params['group_id'] = $this->groupID;
        return [$method, $params];
    }
}