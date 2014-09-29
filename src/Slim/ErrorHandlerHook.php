<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Slim;

use Exception;
use PDOException;
use Psr\Log\LoggerInterface;
use Slim\Http\Response;
use Slim\Slim;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Twig_Template;

/**
 * Define error page handlers.
 *
 * This hook should be attached to the "slim.before" event.
 */
class ErrorHandlerHook
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var boolean
     */
    private $isDebugMode;

    /**
     * @var Twig_Template
     */
    private $twig;

    /**
     * @param LoggerInterface $logger
     * @param boolean $isDebugMode
     * @param Twig_Template $twig
     */
    public function __construct(LoggerInterface $logger, $isDebugMode, Twig_Template $twig)
    {
        $this->logger = $logger;
        $this->isDebugMode = $isDebugMode;
        $this->twig = $twig;
    }

    /**
     * @param Slim $slim
     * @return null
     */
    public function __invoke(Slim $slim)
    {
        // 404 Error Handler
        $slim->notFound(function () use ($slim) {
            $output = $this->twig->render(['message' => 'Page Not Found']);

            $slim->status(404);
            $slim->response()->write($output);
            $slim->stop();
        });

        // 500 Error Handler
        $slim->error(function (Exception $exception) use ($slim) {
            $message = $exception->getMessage();
            $context = ['exceptionData' => $exception->getTraceAsString()];

            $this->logger->error($message, $context);

            if ($exception instanceof PDOException) {
                $message = "There's a problem with the database. Wait a bit and try again.\n" . $message;
            }

            $output = $this->twig->render(['message' => $message]);

            $slim->status(500);
            $slim->response()->write($output);

            if ($exception instanceof FatalErrorException) {
                // For fatal errors, the response must be rendered manually. Slim cannot handle it.
                $this->renderFatal($slim);
                exit;
            } else {
                $slim->stop();
            }
        });
    }

    /**
     * This code was ripped from Slim\Slim
     *
     * @param Slim $slim
     * @return null
     */
    private function renderFatal(Slim $slim)
    {
        list($status, $headers, $body) = $slim->response()->finalize();

        // Skip cookies

        //Send headers
        if (headers_sent() === false) {
            //Send status
            $type = Response::getMessageForCode($status);
            if (strpos(PHP_SAPI, 'cgi') === 0) {
                header(sprintf('Status: %s', $type));
            } else {
                header(sprintf('HTTP/%s %s', $slim->config('http.version'), $type));
            }

            //Send headers
            foreach ($headers as $name => $value) {
                $hValues = explode("\n", $value);
                foreach ($hValues as $hVal) {
                    header("$name: $hVal", false);
                }
            }
        }

        //Send body, but only if it isn't a HEAD request
        if (!$slim->request()->isHead()) {
            echo $body;
        }
    }
}
