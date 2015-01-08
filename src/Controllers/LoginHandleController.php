<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers;

use Doctrine\ORM\EntityManager;
use MCP\DataType\HttpUrl;
use MCP\Corp\Account\LdapService;
use MCP\Corp\Account\User as LdapUser;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class LoginHandleController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

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
     * @type UrlHelper
     */
    private $url;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type Response
     */
    private $response;

    /**
     * @param TemplateInterface $template
     * @param LdapService $ldap
     * @param UserRepository $userRepo
     * @param EntityManager $em
     * @param Session $session
     * @param UrlHelper $url
     * @param Request $request
     * @param Response $response
     */
    public function __construct(
        TemplateInterface $template,
        LdapService $ldap,
        UserRepository $userRepo,
        EntityManager $em,
        Session $session,
        UrlHelper $url,
        Request $request,
        Response $response
    ) {
        $this->template = $template;
        $this->ldap = $ldap;
        $this->userRepo = $userRepo;
        $this->em = $em;
        $this->session = $session;
        $this->url = $url;

        $this->request = $request;
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        $redirect = $this->request->get('redirect', null);

        // auth empty
        if (!$username || !$password) {
            $this->response->setBody($this->bailout('A username and password must be entered.'));
            return;
        }

        // auth failed
        if (!$account = $this->ldap->authenticate($username, $password)) {
            $this->response->setBody($this->bailout('Authentication failed.'));
            return;
        }

        $user = $this->userRepo->find($account->commonId());

        // account disabled manually
        if ($user && !$user->isActive()) {
            $this->response->setBody($this->bailout('Account disabled.'));
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
        $this->session->set('user_id', $user->getId());
        $this->session->set('is-first-login', $isFirstLogin);

        if ($redirect) {
            $this->response->redirect($redirect);
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
