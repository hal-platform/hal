<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use QL\Panthor\Utility\URI;

trait RedirectableControllerTrait
{
    /**
     * Redirect to a route.
     *
     * @param ResponseInterface $response
     * @param URI $uri
     * @param string $route
     * @param array $params
     * @param array $query
     *
     * @return ResponseInterface
     */
    private function withRedirectRoute(
        ResponseInterface $response,
        URI $uri,
        string $route,
        array $params = [],
        array $query = []
    ): ResponseInterface {
        $location = $uri->uriFor($route, $params, $query);
        return $response
            ->withStatus(302)
            ->withHeader('Location', $location);
    }

    /**
     * Redirect to an absolute URI.
     *
     * @param ResponseInterface $response
     * @param string $uri
     *
     * @return ResponseInterface
     */
    private function withRedirectAbsoluteURL(ResponseInterface $response, string $uri): ResponseInterface
    {
        return $response
            ->withStatus(302)
            ->withHeader('Location', $uri);
    }

    /**
     * Redirect to a local URI from the given path.
     *
     * You must provide the current request URI to retrieve the scheme and host.
     *
     * @param ResponseInterface $response
     * @param UriInterface $uri
     * @param string $path
     * @param array $query
     *
     * @return ResponseInterface
     */
    private function withRedirectURL(
        ResponseInterface $response,
        UriInterface $uri,
        string $path,
        array $query = []
    ): ResponseInterface {

        $location = (string) $uri
            ->withUserInfo('')
            ->withPath($path)
            ->withQuery(http_build_query($query))
            ->withFragment('');

        return $response
            ->withStatus(302)
            ->withHeader('Location', $location);
    }
}
