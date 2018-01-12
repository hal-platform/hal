<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Middleware\ACL;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\User;
use Hal\Core\Entity\User\UserToken;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Security\UserSessionHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
    const REGEX_BEARER_AUTH = '#^(?:bearer|oauth|token) ([0-9a-zA-Z]{32,32})$#i';

    private const ERR_AUTH_HEADER_INVALID = 'Authorization access token is missing or invalid';
    private const ERR_INVALID_TOKEN = 'Access token is invalid';
    private const ERR_UNAVAILABLE = 'User account "%s" is unavailable.';

    /**
     * @var EntityRepository
     */
    private $tokenRepo;

    /**
     * @var UserSessionHandler
     */
    private $userHandler;

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
     * @param UserSessionHandler $userHandler
     * @param ProblemRendererInterface $renderer
     */
    public function __construct(EntityManagerInterface $em, UserSessionHandler $userHandler, ProblemRendererInterface $renderer)
    {
        $this->tokenRepo = $em->getRepository(UserToken::class);

        $this->userHandler = $userHandler;
        $this->renderer = $renderer;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (!$token = $this->validateAuthorizationHeader($request)) {
            return $this->withProblem($this->renderer, $response, 403, self::ERR_AUTH_HEADER_INVALID);
        }

        if (!$user = $this->validateToken($token)) {
            return $this->withProblem($this->renderer, $response, 403, self::ERR_INVALID_TOKEN);
        }

        $request = $this->userHandler->attachUserToRequest($request, $user->id());

        // User is disabled or not found for some reason.
        if (!$request) {
            return $this->withProblem($this->renderer, $response, 403, sprintf(self::ERR_UNAVAILABLE, $user->name()));
        }

        $this->attachUserToLogger($user);

        return $next($request, $response);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return string|null
     */
    private function validateAuthorizationHeader(ServerRequestInterface $request): ?string
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
        $token = $this->tokenRepo->findOneBy(['value' => $token]);
        if (!$token instanceof UserToken) {
            return null;
        }

        return $token->user();
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
     * @param User $user
     *
     * @return void
     */
    private function attachUserToLogger(User $user)
    {
        if (!$this->factory) {
            return;
        }

        $name = sprintf('Token: %s', $user->name());
        $this->factory->setDefaultProperty(MessageInterface::USER_NAME, $name);
    }
}
