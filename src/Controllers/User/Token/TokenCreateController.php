<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User\Token;

use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use Slim\Http\Request;
use Slim\Http\Response;
use MCP\Corp\Account\User as LdapUser;
use Doctrine\ORM\EntityManager;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Core\Entity\Token;

/**
 * Allow a user to create an API token
 */
class TokenCreateController
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var UserRepository
     */
    private $users;

    /**
     * @var LdapUser
     */
    private $user;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @param EntityManager $em
     * @param UserRepository $users
     * @param LdapUser $user
     * @param UrlHelper $url
     */
    public function __construct(
        EntityManager $em,
        UserRepository $users,
        LdapUser $user,
        UrlHelper $url
    ) {
        $this->em = $em;
        $this->users = $users;
        $this->user = $user;
        $this->url = $url;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = null, callable $notFound = null)
    {
        $label = $request->post('label', null);

        if (!$label) {
            // should not happen, fail transparently
            $response->redirect($this->url->urlFor('user.current'), 303);
        }

        $token = new Token();
        $token->setValue(sha1(mt_rand()));
        $token->setUser($this->users->find($this->user->commonId()));
        $token->setLabel($label);
        $this->em->persist($token);

        $response->redirect($this->url->urlFor('user.current'), 303);
    }
}