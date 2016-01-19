<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Application;

use Exception;
use QL\Panthor\ErrorHandling\ExceptionHandler\BaseHandler;

/**
 * Handler for errors for API endpoints
 */
class APIBaseHandler extends BaseHandler
{
    /**
     * {@inheritdoc}
     */
    public function handle(Exception $exception)
    {
        if (!isset($_SERVER['REQUEST_URI']) || substr($_SERVER['REQUEST_URI'], 0, 5) !== '/api/') {
            return false;
        }

        return parent::handle($exception);
    }
}
