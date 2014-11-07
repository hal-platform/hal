<?php

namespace QL\Hal\Controllers;

use Doctrine\ORM\EntityManager;
use MCP\DataType\HttpUrl;
use MCP\Corp\Account\LdapService;
use MCP\Corp\Account\User as LdapUser;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Layout;
use QL\Hal\Session;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 *  Login Handle Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class LoginHandleController
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     *  @var LdapService
     */
    private $ldap;

    /**
     *  @var UserRepository
     */
    private $userRepo;

    /**
     *  @var EntityManager
     */
    private $em;

    /**
     *  @var Session
     */
    private $session;

    /**
     *  @var UrlHelper
     */
    private $url;

    /**
     *  @param Twig_Template $template
     *  @param LdapService $ldap
     *  @param UserRepository $userRepo
     *  @param EntityManager $em
     *  @param Session $session
     *  @param UrlHelper $url
     */
    public function __construct(
        Twig_Template $template,
        LdapService $ldap,
        UserRepository $userRepo,
        EntityManager $em,
        Session $session,
        UrlHelper $url
    ) {
        $this->template = $template;
        $this->ldap = $ldap;
        $this->userRepo = $userRepo;
        $this->em = $em;
        $this->session = $session;
        $this->url = $url;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $username = $request->post('username');
        $password = $request->post('password');
        $redirect = $request->get('redirect', null);

        // auth empty
        if (!$username || !$password) {
            $response->setBody($this->bailout('A username and password must be entered.'));
            return;
        }

        // auth failed
        if (!$account = $this->ldap->authenticate($username, $password)) {
            $response->setBody($this->bailout('Authentication failed.'));
            return;
        }

        $user = $this->userRepo->findOneBy(['id' => $account->commonId()]);

        // account disabled manually
        if ($user && !$user->isActive()) {
            $response->setBody($this->bailout('Account disabled.'));
            return;
        }

        $isFirstLogin = false;
        if (!$user) {
            $isFirstLogin = true;
            $user = new User;
            $user->setIsActive(true);
        }

        $this->updateUserDetails($account, $user);

        $this->session->clear();
        $this->session->set('hal-user', $user);
        $this->session->set('ldap-user', $account);
        $this->session->set('is-first-login', $isFirstLogin);

        if ($redirect) {
            $response->redirect($redirect);
        } else {
            $this->url->redirectFor('dashboard');
        }
    }

    /**
     * @param string $error
     *
     * @return string
     */
    private function bailout($error)
    {
        return $this->template->render(['error' => $error]);
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
