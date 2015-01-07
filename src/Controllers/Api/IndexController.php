<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api;

use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaLinkTrait;
use QL\Panthor\ControllerInterface;

/**
 * API Index Controller
 */
class IndexController implements ControllerInterface
{
    use HypermediaLinkTrait;

    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @param ResponseFormatter $formatter
     */
    public function __construct(ResponseFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $this->formatter->respond([
            '_links' => [
                'environments' => ['href' => 'api.environments'],
                'servers' => ['href' => 'api.servers'],
                'groups' => ['href' => 'api.groups'],
                'users' => ['href' => 'api.users'],
                'repositories' => ['href' => 'api.repositories'],
                'queue' => ['href' => 'api.queue']
            ]
        ]);
    }
}
