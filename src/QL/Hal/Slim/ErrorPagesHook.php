<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Slim;

use Exception;
use Slim\Slim;
use Twig_Template;

/**
 * Define error page handlers.
 *
 * This hook should be attached the "slim.before" event.
 */
class ErrorPagesHook
{
    /**
     * @var Twig_Template
     */
    private $twig;

    /**
     * @param Layout $layout
     * @param Twig_Template $twig
     */
    public function __construct(Twig_Template $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param Slim $app
     * @return null
     */
    public function __invoke(Slim $app)
    {
        // 404 Error Handler
        $app->notFound(function () use ($app) {
            $output = $this->twig->render(['message' => 'Page Not Found']);

            $app->status(404);
            $app->response()->write($output);
            $app->stop();
        });

        // 500 Error Handler
        $app->error(function (Exception $e) use ($app) {
            $output = $this->twig->render(['message' => 'Oh, snap! You broke it.']);

            $app->status(500);
            $app->response()->write($output);
            $app->stop();
        });
    }
}