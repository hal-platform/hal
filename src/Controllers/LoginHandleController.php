<?php

namespace QL\Hal\Controllers;

use Doctrine\ORM\EntityManager;
use MCP\DataType\HttpUrl;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Session;
use Twig_Template;
use MCP\Corp\Account\LdapService;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;

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

        if (!$username || !$password) {

            $rendered = $this->template->render([
                'error' => 'A username and password must be entered.'
            ]);

            $response->body($rendered);
            return;
        }

        $account = $this->ldap->authenticate($username, $password);

        if (!$account) {
            $rendered = $this->template->render([
                'error' => 'Authentication failed.'
            ]);

            $response->body($rendered);
            return;
        }

        $this->session->set('account', $account);
        $this->session->set('isFirstLogin', false);
        $user = $this->userRepo->findOneBy(['id' => $account->commonId()]);

        if (!$user) {
            $this->session->set('isFirstLogin', true);
            $user = new User();
        }

        $user->setId($account->commonId());
        $user->setEmail($account->email());
        $user->setHandle($account->windowsUsername());
        $user->setName($account->displayName());
        $user->setPictureUrl($account->badgePhotoUrl());
        $user->setIsActive(true);

        $this->em->persist($user);
        $this->em->flush();

        if ($redirect) {
            $response->redirect($redirect);
        } else {
            $this->url->redirectFor('dashboard');
        }
    }
}
