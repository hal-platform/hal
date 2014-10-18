<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Bouncers;

use MCP\Corp\Account\LdapService;
use QL\Hal\Core\Entity\User;
use Slim\Http\Request;
use Slim\Http\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A bouncer that checks for a valid Authorization header auth token
 */
class ApiAuthBouncer
{
    const HEADER = 'Authorization';

    const FORMAT = '#^Token ([0-9a-bA-B]{64,64})$#';

    const MATCH_POSITIION = 1;

    private $container;

    private $ldap;

    /**
     * @param ContainerInterface $container
     * @param LdapService $ldap
     */
    public function __construct(ContainerInterface $container, LdapService $ldap)
    {
        $this->container = $container;
        $this->ldap = $ldap;
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        die('nyi');

        $header = $request->headers(self::HEADER, null);

        if (0 === preg_match(self::FORMAT, $header, $matches)) {
            // throw http problem
        }

        if (!is_array($matches) || !isset($matches[self::MATCH_POSITIION])) {
            // throw http problem, can't find token value from header
        }

        $token = $this->tokens->findOneBy(['token' => $matches[self::MATCH_POSITIION]]);

        if (!$token instanceof Token) {
            // throw http problem, no matching token
        }

        $requester = ($token->getUser()) ? $token->getUser() : $token->getConsumer();

        if ($requester instanceof User) {
            if (!$requester->isActive()) {
                // throw http problem, user not active in hal
            }
            if (!$this->ldap->getUserByCommonId($requester->getId())) {
                // throw http problem, user not available from ldap
            }
        }

        // let controller know who or what is connected to the api
        $this->container->set('requester', $requester);
    }
}