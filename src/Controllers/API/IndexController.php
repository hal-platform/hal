<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Api;

use Hal\UI\API\Hyperlink;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;

class IndexController implements ControllerInterface
{
    use APITrait;

    /**
     * @var ResponseFormatter
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
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $body = $this->formatter->buildResponse($request, [
            '_links' => [
                'environments' => new Hyperlink('api.environments'),
                'servers' => new Hyperlink('api.servers'),

                'applications' => new Hyperlink('api.applications'),
                'groups' => new Hyperlink('api.groups'),

                'users' => new Hyperlink('api.users'),
                'queue' => new Hyperlink('api.queue'),

                'documentation' => new Hyperlink('api.docs')
            ]
        ]);

        return $this->withHypermediaEndpoint($request, $response, $body, 200);
    }
}
