<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers;

use QL\Panthor\ControllerInterface;

/**
 * Debug Controller
 */
class DebugController implements ControllerInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        // shoosh, nothing to see here
        phpinfo();
        die();
    }
}
