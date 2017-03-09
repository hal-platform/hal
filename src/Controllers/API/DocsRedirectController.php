<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class DocsRedirectController implements ControllerInterface
{
    /**
     * @var URI
     */
    private $uri;

    /**
     * @param URI $uri
     */
    public function __construct(URI $uri)
    {
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $location = (string) $request->getUri()
            ->withUserInfo('')
            ->withPath('/docs/api/index.html')
            ->withQuery('')
            ->withFragment('');

        return $response
            ->withStatus(301)
            ->withHeader('Location', $location);
    }
}