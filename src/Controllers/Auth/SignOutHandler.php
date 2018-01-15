<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Auth;

use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class SignOutHandler implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;

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
        $this
            ->getSession($request)
            ->clear();

        return $this->withRedirectRoute($response, $this->uri, 'signin');
    }
}
