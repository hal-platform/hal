<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Slim;

use QL\HttpProblem\HttpProblemException;
use QL\Panthor\ErrorHandling\ExceptionConfigurator as BaseConfigurator;
use QL\Panthor\Exception\NotFoundException;

class ExceptionConfigurator extends BaseConfigurator
{
    /**
     * Replacement of default 404 handler to support api and html responses.
     * {@inheritdoc}
     */
    public function handleNotFoundException(NotFoundException $exception)
    {
        if (isset($_SERVER['REQUEST_URI']) && substr($_SERVER['REQUEST_URI'], 0, 5) === '/api/') {
            return $this->handleHttpProblemException(HttpProblemException::build(404, 'not-found'));
        }

        $this->renderTwigResponse($exception, 404);
    }
}
