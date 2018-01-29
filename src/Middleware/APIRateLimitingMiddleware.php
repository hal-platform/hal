<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware;

use Hal\Core\Entity\User;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Service\APIRateLimitService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;
use Slim\Route;

class APIRateLimitingMiddleware implements MiddlewareInterface
{
    use APITrait;
    use SessionTrait;

    private const ERR_RATE_LIMIT = 'Rate limit exceeded for %s. Please wait a moment.';

    /**
     * @var ProblemRendererInterface
     */
    private $problemRenderer;

    /**
     * @var APIRateLimitService
     */
    private $rateLimiter;

    /**
     * @param ProblemRendererInterface $problemRenderer
     * @param APIRateLimitService $rateLimiter
     */
    public function __construct(ProblemRendererInterface $problemRenderer, APIRateLimitService $rateLimiter)
    {
        $this->problemRenderer = $problemRenderer;
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $user = $this->getUser($request);
        $routeName = $this->getRouteName($request);

        if ($this->rateLimiter->isLimited($user, $routeName)) {
            $msg = sprintf(self::ERR_RATE_LIMIT, $user->name());
            return $this->withProblem($this->problemRenderer, $response, 429, $msg);
        }

        $response = $next($request, $response);

        $this->bump($response, $user, $routeName);

        return $response;
    }

    /**
     * @param ResponseInterface $response
     * @param User|null $user
     * @param string $routeName
     *
     * @return void
     */
    private function bump(ResponseInterface $response, ?User $user, $routeName)
    {
        // Round status code to hundreds.
        $status = (int) floor($response->getStatusCode() / 100) * 100;

        // Bump the rate limiter if the response was a client error or success.
        if (in_array($status, [200, 400], true)) {
            $this->rateLimiter->increment($user, $routeName);
        }
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    private function getRouteName(ServerRequestInterface $request): string
    {
        $route = $request->getAttribute('route');
        if ($route instanceof Route) {
            return $route->getName();
        }

        return 'unknown';
    }
}
