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
use QL\ExceptionToolkit\ExceptionDispatcher;
use QL\HttpProblem\Formatter\JsonFormatter;
use QL\HttpProblem\HttpProblemException;
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
    private $dispatcher;

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
     * @param ExceptionDispatcher $dispatcher
     * @param LoggerInterface $logger
     * @param $isDebugMode
     * @param Twig_Template $twig
     */
    public function __construct(
        ExceptionDispatcher $dispatcher,
        LoggerInterface $logger,
        $isDebugMode,
        Twig_Template $twig
    ) {
        $this->dispatcher = $dispatcher;
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
        // Register Not Found Handler
        $slim->notFound(function () use ($slim) {
            $this->prepareTwigResponse($slim, 'Page Not Found', 404);
            $slim->stop();
        });

        // Register Exception Handlers
        $slim->error(function (Exception $exception) use ($slim) {
            $this->dispatcher->dispatch($exception);
            $slim->stop();
        });

        // Handle Http Problems
        $this->dispatcher->add(function (HttpProblemException $exception) use ($slim) {
            $this->prepareResponse(
                $slim,
                JsonFormatter::content($exception->problem()),
                JsonFormatter::status($exception->problem()),
                JsonFormatter::headers($exception->problem())
            );
        });

        // Handle Fatal Exceptions
        $this->dispatcher->add(function (FatalErrorException $exception) use ($slim) {
            $this->prepareTwigResponse($slim, $exception->getMessage());
            $this->forceSendResponse($slim);
        });

        // Handle PDO Exceptions
        $this->dispatcher->add(function (PDOException $exception) use ($slim) {
            $this->sendLog($exception->getMessage(), ['exceptionData' => $exception->getTraceAsString()]);
            $this->prepareTwigResponse(
                $slim,
                sprintf("There's a problem with the database. Wait a bit and try again.\n%s", $exception->getMessage())
            );
        });

        // Handle All Other Exceptions
        $this->dispatcher->add(function (Exception $exception) use ($slim) {
            $this->sendLog($exception->getMessage(), ['exceptionData' => $exception->getTraceAsString()]);
            $this->prepareTwigResponse($slim, $exception->getMessage());
        });
    }

    /**
     * Prepare Twig formatted response for output
     *
     * @param Slim $slim
     * @param string $message
     * @param int $status
     * @param array $headers
     */
    public function prepareTwigResponse(Slim $slim, $message, $status = 500, $headers = [])
    {
        $this->prepareResponse($slim, $this->twig->render(['message' => $message]), $status, $headers);
    }

    /**
     * Prepare response for output
     *
     * @param Slim $slim
     * @param string $body
     * @param int $status
     * @param array $headers
     */
    public function prepareResponse(Slim $slim, $body, $status = 500, $headers = [])
    {
        $slim->response->setBody($body);
        $slim->response->setStatus($status);
        foreach ($headers as $key => $value) {
            $slim->response->header($key, $value);
        }
    }

    /**
     * Create and send a log message
     *
     * @param $message
     * @param $context
     */
    public function sendLog($message, $context = [])
    {
        $this->logger->error($message, $context);
    }

    /**
     * Force sending of the response
     *
     * @param Slim $slim
     */
    public function forceSendResponse(Slim $slim)
    {
        list($status, $headers, $body) = $slim->response()->finalize();

        if (headers_sent() === false) {
            if (strpos(PHP_SAPI, 'cgi') === 0) {
                header(sprintf('Status: %s', Response::getMessageForCode($status)));
            } else {
                header(sprintf('HTTP/%s %s', $slim->config('http.version'), Response::getMessageForCode($status)));
            }

            // send headers
            foreach ($headers as $name => $value) {
                $hValues = explode("\n", $value);
                foreach ($hValues as $hVal) {
                    header("$name: $hVal", false);
                }
            }
        }

        // do not set body for HEAD requests
        if ($slim->request->isHead()) {
            return;
        }

        echo $body;
        exit();
    }
}
