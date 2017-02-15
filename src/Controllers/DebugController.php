<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use QL\Panthor\ControllerInterface;

class DebugController implements ControllerInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        // shoosh, nothing to see here
        phpinfo();
        die();
    }
}
