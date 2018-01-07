<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use Hal\UI\Middleware\CSRFMiddleware;
use Psr\Http\Message\ServerRequestInterface;

trait CSRFTrait
{
    /**
     * @return bool
     */
    private function isCSRFValid(ServerRequestInterface $request): bool
    {
        $isError = $request->getAttribute(CSRFMiddleware::CSRF_ERROR_ATTRIBUTE);

        if ($isError === true) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    private function CSRFError(): string
    {
        return 'CSRF validation failed. Please try again.';
    }
}
