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

class DocsRedirectController implements ControllerInterface
{
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
