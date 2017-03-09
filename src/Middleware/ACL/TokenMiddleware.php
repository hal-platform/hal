<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware\ACL;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Middleware\UserSessionGlobalMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Token;
use QL\Hal\Core\Entity\User;
use QL\MCP\Logger\MessageFactoryInterface;
use QL\MCP\Logger\MessageInterface;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;

/**
 * Possible error codes for oauth token failure:
 * @see https://tools.ietf.org/html/rfc6749#section-5.2
 */
class TokenMiddleware implements MiddlewareInterface
{
    use APITrait;

    const HEADER_NAME = 'Authorization';
    const REGEX_BEARER_AUTH = '#^(?:bearer|oauth|token) ([0-9a-zA-Z]{40,40})$#i';

    private const ERR_AUTH_HEADER_INVALID = 'Authorization access token is missing is invalid';
    private const ERR_INVALID_TOKEN = 'Access token is invalid';
    private const ERR_DISABLED = 'User account "%s" is disabled.';

    /**
     * @var EntityRepository
     */
    private $tokenRepo;

    /**
     * @var ProblemRendererInterface
     */
    private $renderer;

    /**
     * @var MessageFactoryInterface|null
     */
    private $factory;

    /**
     * @param EntityManagerInterface $em
     * @param ProblemRendererInterface $renderer
     */
    public function __construct(
        EntityManagerInterface $em,
        ProblemRendererInterface $renderer
    ) {
        $this->tokenRepo = $em->getRepository(Token::class);
        $this->renderer = $renderer;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (!$token = $this->validateAuthorization($request)) {
            return $this->withProblem($this->renderer, $response, 403, self::ERR_AUTH_HEADER_INVALID);
        }

        if (!$user = $this->validateToken($token)) {
            return $this->withProblem($this->renderer, $response, 403, self::ERR_INVALID_TOKEN);
        }

        if (!$user->isActive()) {
            return $this->withProblem($this->renderer, $response, 403, sprintf(self::ERR_DISABLED, $user->handle()));
        }

        if ($this->factory) {
            $this->factory->setDefaultProperty(MessageInterface::USER_NAME, sprintf('Token: %s', $user->handle()));
        }

        // Add user to the server attrs for controllers/middleware
        $request = $request->withAttribute(UserSessionGlobalMiddleware::USER_ATTRIBUTE, $user);

        return $next($request, $response);
    }

    /**
     * @param MessageFactoryInterface $factory
     *
     * @return void
     */
    public function setLoggerMessageFactory(MessageFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return string|null
     */
    private function validateAuthorization(ServerRequestInterface $request): ?string
    {
        $authorization = $request->getHeaderLine(self::HEADER_NAME);

        if (!$authorization) {
            return null;
        }

        // Validate the header
        if (preg_match(static::REGEX_BEARER_AUTH, $authorization, $match) !== 1) {
            return null;
        }

        $token = array_pop($match);
        return $token;
    }

    /**
     * @param string $token
     *
     * @return User|null
     */
    private function validateToken($token): ?User
    {
        if (!$token = $this->tokenRepo->findOneBy(['value' => $token])) {
            return null;
        }

        return $token->user();
    }
}
