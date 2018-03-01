<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Security\UserAuthentication;

use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\Utility\URI;

class OAuthCallbackFactory
{
    /**
     * @var ServerRequestInterface
     */
    private $baseRequest;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var string
     */
    private $routeName;

    /**
     * @param ServerRequestInterface $baseRequest
     * @param URI $uri
     * @param string $routeName
     */
    public function __construct(ServerRequestInterface $baseRequest, URI $uri, string $routeName)
    {
        $this->baseRequest = $baseRequest;
        $this->uri = $uri;

        $this->routeName = $routeName;
    }

    /**
     * @return string
     */
    public function getFullCallbackURL(): string
    {
        $uri = $this->baseRequest->getUri();

        return $this->uri->absoluteURIFor($uri, $this->routeName);
    }
}
