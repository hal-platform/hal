<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers;

use Doctrine\ORM\EntityManager;
use MCP\Corp\Account\LdapService;
use MCP\Corp\Account\User as LdapUser;
use QL\Hal\Core\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Session;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Twig\Context;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;

class LoginHandler implements MiddlewareInterface
{
    const ERR_INVALID = 'A username and password must be entered.';
    const ERR_AUTH_FAILURE = 'Authentication failed.';
    const ERR_DISABLED = 'Account disabled.';

    /**
     * @type Context
     */
    private $context;

    /**
     * @type LdapService
     */
    private $ldap;

    /**
     * @type UserRepository
     */
    private $userRepo;

    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @type Session
     */
    private $session;

    /**
     * @type Url
     */
    private $url;

    /**
     * @type Request
     */
    private $request;

    /**
     * @param Context $context
     * @param LdapService $ldap
     * @param UserRepository $userRepo
     * @param EntityManager $em
     * @param Session $session
     * @param Url $url
     * @param Request $request
     */
    public function __construct(
        Context $context,
        LdapService $ldap,
        UserRepository $userRepo,
        EntityManager $em,
        Session $session,
        Url $url,
        Request $request
    ) {
        $this->context = $context;
        $this->ldap = $ldap;
        $this->userRepo = $userRepo;
        $this->em = $em;
        $this->session = $session;
        $this->url = $url;

        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$this->request->isPost()) {
            return;
        }

        $username = $this->request->post('username');
        $password = $this->request->post('password');
        $redirect = $this->request->get('redirect', null);

        // auth empty
        if (!$username || !$password) {
            return $this->context->addContext(['errors' => [self::ERR_INVALID]]);
        }

        // auth failed
        if (!$account = $this->ldap->authenticate($username, $password)) {
            return $this->context->addContext(['errors' => [self::ERR_AUTH_FAILURE]]);
        }

        $user = $this->userRepo->find($account->commonId());

        // account disabled manually
        if ($user && !$user->isActive()) {
            return $this->context->addContext(['errors' => [self::ERR_DISABLED]]);
        }

        $isFirstLogin = false;
        if (!$user) {
            $isFirstLogin = true;
            $user = new User;
            $user->setIsActive(true);
        }

        $this->updateUserDetails($account, $user);

        $this->session->clear();
        $this->session->set('user_id', $user->getId());
        $this->session->set('is-first-login', $isFirstLogin);

        if ($redirect) {
            $this->url->redirectForURL($redirect);
        } else {
            $this->url->redirectFor('dashboard');
        }
    }

    /**
     * @param LdapUser $account
     * @param User $user
     *
     * @return null
     */
    private function updateUserDetails(LdapUser $account, User $user)
    {
        // Update user
        $user->setId($account->commonId());
        $user->setEmail($account->email());
        $user->setHandle($account->windowsUsername());
        $user->setName($account->displayName());
        $user->setPictureUrl($account->badgePhotoUrl());

        $this->em->persist($user);
        $this->em->flush();
    }
}
