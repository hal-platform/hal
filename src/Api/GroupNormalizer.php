<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api;

use QL\Hal\Core\Entity\Group;
use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Helpers\UrlHelper;

class GroupNormalizer
{
    /**
     * @type ApiHelper
     */
    private $api;

    /**
     * @type UrlHelper
     */
    private $url;

    /**
     * @param ApiHelper $api
     * @param UrlHelper $url
     */
    public function __construct(ApiHelper $api, UrlHelper $url)
    {
        $this->api = $api;
        $this->url = $url;
    }

    /**
     * Normalize to the standard linked resource.
     *
     * @param Group $group
     * @return array
     */
    public function linked(Group $group)
    {
        return $this->api->parseLink([
            'href' => ['api.group', ['id' => $group->getId()]],
            'title' => $group->getKey()
        ]);
    }

    /**
     * Normalize to the full entity properties.
     *
     * If specified, linked resources will be fully resolved.
     *
     * @param Group $group
     * @return array
     */
    public function normalize(Group $group, array $criteria = [])
    {
        $content = [
            'id' => $group->getId(),
            'url' => $this->url->urlFor('group', ['id' => $group->getId()]),
            'key' => $group->getKey(),
            'name' => $group->getName(),
        ];

        return array_merge_recursive($content, $this->links($group));
    }

    /**
     * @param Group $group
     * @return array
     */
    private function links(Group $group)
    {
        return [
            '_links' => [
                'self' => $this->linked($group),
                'index' => $this->api->parseLink(['href' => 'api.groups'])
            ]
        ];
    }
}
