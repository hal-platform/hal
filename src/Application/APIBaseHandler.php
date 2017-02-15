<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Application;

use Exception;
use QL\Panthor\ErrorHandling\ExceptionHandler\BaseHandler;

class APIBaseHandler extends BaseHandler
{
    /**
     * @inheritDoc
     */
    public function handle($throwable)
    {
        if (!isset($_SERVER['REQUEST_URI']) || substr($_SERVER['REQUEST_URI'], 0, 5) !== '/api/') {
            return false;
        }

        return parent::handle($throwable);
    }
}
