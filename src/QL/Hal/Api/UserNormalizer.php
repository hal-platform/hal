<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api;

use QL\Hal\Core\Entity\User;
use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Helpers\UrlHelper;

class UserNormalizer
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
     * @param User $user
     * @return array
     */
    public function normalizeLinked(User $user)
    {
        $content = [
            'id' => $user->getId()
        ];

        $content = array_merge($content, $this->links($user));

        return $content;
    }

    /**
     * Normalize to the full entity properties.
     *
     * If specified, linked resources will be fully resolved.
     *
     * @param User $user
     * @return array
     */
    public function normalize(User $user, array $criteria = [])
    {
        $content = [
            'id' => $user->getId(),
            'url' => $this->url->urlFor('user', ['id' => $user->getId()]),
            'handle' => $user->getHandle(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'picture' => $user->getPictureUrl()->asString()
        ];

        $content = array_merge($content, $this->links($user));

        return $content;
    }

    /**
     * @param User $user
     * @return array
     */
    private function links(User $user)
    {
        return [
            '_links' => $this->api->parseLinks([
                'self' => ['href' => ['api.user', ['id' => $user->getId()]]],
                'index' => ['href' => 'api.users', 'type' => 'Index']
            ])
        ];
    }
}
