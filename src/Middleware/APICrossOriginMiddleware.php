<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware;

use Hal\UI\Controllers\APITrait;
use Neomerx\Cors\Analyzer;
use Neomerx\Cors\Contracts\AnalyzerInterface;
use Neomerx\Cors\Contracts\AnalysisResultInterface;
use Neomerx\Cors\Contracts\Strategies\SettingsStrategyInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\HTTPProblem\HTTPProblem;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;
use QL\Panthor\MiddlewareInterface;

class APICrossOriginMiddleware implements MiddlewareInterface
{
    use APITrait;

    private const ERR_INVALID_CORS = 'Invalid CORS request';

    /**
     * @var SettingsStrategyInterface
     */
    private $corsSettings;

    /**
     * @var ProblemRendererInterface
     */
    private $problemRenderer;

    /**
     * @param SettingsStrategyInterface $corsSettings
     * @param ProblemRendererInterface $problemRenderer
     */
    public function __construct(SettingsStrategyInterface $corsSettings, ProblemRendererInterface $problemRenderer)
    {
        $this->corsSettings = $corsSettings;
        $this->problemRenderer = $problemRenderer;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($route = $request->getAttribute('route')) {
            $this->corsSettings->setRequestAllowedMethods(array_fill_keys($route->getMethods(), true));
        }

        $uri = $request->getUri();
        $this->corsSettings->setServerOrigin([
            'scheme' => $uri->getScheme(),
            'host' => $uri->getHost(),
            'port' => $uri->getPort()
        ]);

        $analyzer = Analyzer::instance($this->corsSettings);

        $cors = $analyzer->analyze($request);

        $type = $cors->getRequestType();
        $headers = $cors->getResponseHeaders();

        if ($type === AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE) {
            return $next($request, $response);
        }

        if ($type === AnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST || $type === AnalysisResultInterface::TYPE_ACTUAL_REQUEST) {
            foreach ($headers as $name => $value) {
                $response = $response->withHeader($name, $value);
            }

            return $next($request, $response);
        }

        // AnalysisResultInterface::ERR_NO_HOST_HEADER
        // AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED
        // AnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED
        // AnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED

        switch ($type) {
            case AnalysisResultInterface::ERR_NO_HOST_HEADER:
                $error = 'No host header';
                break;

            case AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED:
                $error = 'Origin not allowed';
                break;

            case AnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED:
                $error = 'Method not supported';
                break;

            case AnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED:
                $error = 'Headers not supported';
                break;

            default:
                $error = 'Unknown CORS error';
        }

        $problem = new HTTPProblem(403, self::ERR_INVALID_CORS, ['errors' => [$error]]);
        return $this->renderProblem($response, $this->problemRenderer, $problem);
    }
}
