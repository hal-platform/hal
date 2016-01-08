<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Middleware\ACL;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\Logger\MessageFactoryInterface;
use QL\Hal\Core\Entity\Token;
use QL\Hal\Core\Entity\User;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Exception\HTTPProblemException;
use Slim\Http\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Possible error codes for oauth token failure:
 * @see https://tools.ietf.org/html/rfc6749#section-5.2
 */
class TokenMiddleware implements MiddlewareInterface
{
    const HEADER_NAME = 'Authorization';
    const REGEX_BEARER_AUTH = '#^(?:bearer|oauth|token) ([0-9a-zA-Z]{40,40})$#i';

    const ERR_AUTH_REQUIRED = 'Token authorization is required';
    const ERR_AUTH_IS_WEIRD = 'Authorization type or access token is invalid';
    const ERR_INVALID_TOKEN = 'Access token is invalid';
    const ERR_DISABLED = 'User account "%s" is disabled.';

    /**
     * @type ContainerInterface
     */
    private $di;

    /**
     * @type EntityRepository
     */
    private $tokenRepo;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type MessageFactoryInterface
     */
    private $logFactory;

    /**
     * @param ContainerInterface $di
     * @param EntityManagerInterface $em
     * @param Request $request
     * @param MessageFactoryInterface $logFactory
     */
    public function __construct(
        ContainerInterface $di,
        EntityManagerInterface $em,
        Request $request,
        MessageFactoryInterface $logFactory
    ) {
        $this->di = $di;
        $this->tokenRepo = $em->getRepository(Token::CLASS);

        $this->request = $request;
        $this->logFactory = $logFactory;
    }

    /**
     * {@inheritdoc}
     * @throws HTTPProblemException
     */
    public function __invoke()
    {
        $token = $this->validateAuthorization();

        $user = $this->validateToken($token);

        $this->logFactory->setDefaultProperty('userCommonId', $user->id());
        $this->logFactory->setDefaultProperty('userName', 'Token: ' . $user->handle());
        $this->logFactory->setDefaultProperty('userDisplayName', $user->name());

        $this->di->set('currentUser', $user);
    }

    /**
     * @throws HTTPProblemException
     *
     * @return string
     */
    private function validateAuthorization()
    {
        if (!$authorization = $this->request->headers['Authorization']) {
            throw new HTTPProblemException(403, self::ERR_AUTH_REQUIRED);
        }

        // Validate the header
        if (preg_match(static::REGEX_BEARER_AUTH, $authorization, $match) !== 1) {
            throw new HTTPProblemException(403, self::ERR_AUTH_IS_WEIRD);
        }

        $token = array_pop($match);
        return $token;
    }

    /**
     * @param string $token
     *
     * @throws HTTPProblemException
     *
     * @return User
     */
    private function validateToken($token)
    {
        if (!$token = $this->tokenRepo->findOneBy(['value' => $token])) {
            throw new HTTPProblemException(403, self::ERR_INVALID_TOKEN);
        }

        $user = $token->user();

        if (!$user->isActive()) {
            throw new HTTPProblemException(403, sprintf(self::ERR_DISABLED, $user->handle()));
        }

        return $user;
    }
}
