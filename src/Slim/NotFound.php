<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Slim;

use Slim\Slim;

/**
 * Simple proxy for Slim::notFound
 */
class NotFound
{
    /**
     * @type Slim
     */
    private $slim;

    /**
     * @param Slim $slim
     */
    public function __construct(Slim $slim)
    {
        $this->slim = $slim;
    }

    /**
     * @see Slim::notFound
     */
    public function __invoke()
    {
        return $this->slim->notFound();
    }
}
