<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api;

use QL\Hal\Api\Hyperlink;
use QL\Hal\Api\ResponseFormatter;
use QL\Panthor\ControllerInterface;

class IndexController implements ControllerInterface
{
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
                'environments' => new Hyperlink('api.environments'),
                'servers' => new Hyperlink('api.servers'),

                'applications' => new Hyperlink('api.applications'),
                'groups' => new Hyperlink('api.groups'),

                'users' => new Hyperlink('api.users'),
                'queue' => new Hyperlink('api.queue'),
            ]
        ]);
    }
}
