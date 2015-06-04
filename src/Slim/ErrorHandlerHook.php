<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Slim;

use ErrorException;
use Exception;
use PDOException;
use Psr\Log\LoggerInterface;
use QL\ExceptionToolkit\ExceptionDispatcher;
use QL\HttpProblem\Formatter\JsonFormatter;
use QL\HttpProblem\HttpProblemException;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;
use Slim\Slim;
use Symfony\Component\Debug\Exception\FatalErrorException;

/**
 * Define error page handlers.
 *
 * This hook should be attached to the "slim.before" event.
 */
class ErrorHandlerHook
{
    /**
     * @type array
     */
    private $levels = array(
        E_WARNING           => 'Warning',
        E_NOTICE            => 'Notice',
        E_USER_ERROR        => 'User Error',
        E_USER_WARNING      => 'User Warning',
        E_USER_NOTICE       => 'User Notice',
        E_STRICT            => 'Runtime Notice',
        E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
        E_DEPRECATED        => 'Deprecated',
        E_USER_DEPRECATED   => 'User Deprecated',
        E_ERROR             => 'Error',
        E_CORE_ERROR        => 'Core Error',
        E_COMPILE_ERROR     => 'Compile Error',
        E_PARSE             => 'Parse Error',
    );

    /**
     * @type ExceptionDispatcher
     */
    private $dispatcher;

    /**
     * @type LoggerInterface
     */
    private $logger;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @param ExceptionDispatcher $dispatcher
     * @param LoggerInterface $logger
     * @param TemplateInterface $template
     */
    public function __construct(
        ExceptionDispatcher $dispatcher,
        LoggerInterface $logger,
        TemplateInterface $template
    ) {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->template = $template;
    }

    /**
     * @param Slim $slim
     * @return null
     */
    public function __invoke(Slim $slim)
    {
        $this->registerHandlers($slim);

        // Register Not Found Handler
        $slim->notFound(function () use ($slim) {
            $rendered = $this->template->render(['status' => 404]);
            $this->prepareResponse($slim, $rendered, 404);
            $slim->stop();
        });

        // Register Global Exception Handler
        $slim->error(function (Exception $exception) use ($slim) {
            $this->dispatcher->dispatch($exception);
            $slim->stop();
        });
    }

    /**
     * @param Slim $slim
     * @return null
     */
    private function registerHandlers(Slim $slim)
    {
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
            $this->prepareTwigResponse($slim, $exception);
            $this->forceSendResponse($slim);
        });

        // Handle PDO Exceptions
        $this->dispatcher->add(function (PDOException $exception) use ($slim) {
            $this->prepareTwigResponse($slim, $exception);
        });

        // Handle All Other Exceptions
        $this->dispatcher->add(function (Exception $exception) use ($slim) {
            $this->prepareTwigResponse($slim, $exception);
        });
    }

    /**
     * Prepare Twig formatted response for output
     *
     * @param Slim $slim
     * @param Exception $exception
     * @param int $status
     */
    private function prepareTwigResponse(Slim $slim, Exception $exception, $status = 500)
    {
        if ($status >= 500) {
            // Special note: HAL uses error log level. Agent uses critical log level.
            $context = [
                'exceptionClass' => get_class($exception),
                'exceptionData' => $exception instanceof FatalErrorException ? (string) $exception : $exception->getTraceAsString(),
            ];

            if ($previous = $exception->getPrevious()) {
                $context['previousExceptionClass'] = get_class($previous);
                $context['previousExceptionData'] = $previous->getTraceAsString();
            }

            $this->logger->error($exception->getMessage(), $context);
        }

        $context = [
            'message' => $exception->getMessage(),
            'status' => $status
        ];

        if ($exception instanceof ErrorException && array_key_exists($exception->getSeverity(), $this->levels)) {
            $context['type'] = $this->levels[$exception->getSeverity()];
        }

        $rendered = $this->template->render($context);

        $this->prepareResponse($slim, $rendered, $status);
    }

    /**
     * Prepare response for output
     *
     * @param Slim $slim
     * @param string $body
     * @param int $status
     * @param array $headers
     */
    private function prepareResponse(Slim $slim, $body, $status = 500, $headers = [])
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
    private function sendLog($message, $context = [])
    {
        $this->logger->error($message, $context);
    }

    /**
     * Force sending of the response
     *
     * @param Slim $slim
     */
    private function forceSendResponse(Slim $slim)
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
