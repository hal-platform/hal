<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Bouncers;

use MCP\Corp\Account\LdapService;
use QL\Hal\Core\Entity\Repository\TokenRepository;
use QL\Hal\Core\Entity\Token;
use QL\Hal\Core\Entity\User;
use QL\HttpProblem\HttpProblemException;
use Slim\Http\Request;
use Slim\Http\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A bouncer that checks for a valid Authorization header auth token
 */
class ApiAuthBouncer
{
    const HEADER = 'Authorization';
    const FORMAT = '#^Token ([0-9a-zA-Z]{40,40})$#';
    const MATCH_POSITION = 1;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var LdapService
     */
    private $ldap;

    /**
     * @var TokenRepository
     */
    private $tokens;

    /**
     * @param ContainerInterface $container
     * @param LdapService $ldap
     * @param TokenRepository $tokens
     */
    public function __construct(
        ContainerInterface $container,
        LdapService $ldap,
        TokenRepository $tokens
    ) {
        $this->container = $container;
        $this->ldap = $ldap;
        $this->tokens = $tokens;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @throws HttpProblemException
     */
    public function __invoke(Request $request, Response $response)
    {
        // slim is not passing the auth header, get it the hard way
        $headers = getallheaders();

        if (!is_array($headers) || !isset($headers[self::HEADER])) {
            throw HttpProblemException::build(403, sprintf('Access denied. Missing %s header.', self::HEADER));
        }

        if (0 === preg_match(self::FORMAT, $headers[self::HEADER], $matches)) {
            throw HttpProblemException::build(403, sprintf('Access denied. Invalid %s header format.', self::HEADER));
        }

        if (!is_array($matches) || !isset($matches[self::MATCH_POSITION])) {
            throw HttpProblemException::build(403, sprintf('Access denied. Invalid %s header format.', self::HEADER));
        }

        $token = $this->tokens->findOneBy(['value' => $matches[self::MATCH_POSITION]]);

        if (!$token instanceof Token) {
            throw HttpProblemException::build(403, sprintf('Access denied. Invalid token.', self::HEADER));
        }

        $requester = ($token->getUser()) ? $token->getUser() : $token->getConsumer();

        if ($requester instanceof User) {
            if (!$requester->isActive()) {
                throw HttpProblemException::build(403, sprintf('Access denied. User has been marked as inactive.', self::HEADER));
            }
            if (!$this->ldap->getUserByCommonId($requester->getId())) {
                throw HttpProblemException::build(403, sprintf('Access denied. User cannot be located.', self::HEADER));
            }
        }

        // let controller know who or what is connected to the api
        $this->container->set('requester', $requester);
    }
}