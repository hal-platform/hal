<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Designed for use with controllers that do not use any data from the request.
 *
 * It simply makes the dependencies of these controllers more explicit by not requiring
 * awareness of ServerRequestInterface or RequestInterface.
 *
 * Controllers MUST implement the following method:
 * ```
 * public function execute(ResponseInterface $response): ResponseInterface;
 * ```
 */
trait ResponseOnlyControllerTrait
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $this->execute($response);
    }
}
