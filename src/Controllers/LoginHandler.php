<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Auth;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserSettings;
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
     * @var Context
     */
    private $context;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var UserRepository
     */
    private $userRepo;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Url
     */
    private $url;

    /**
     * @var callable
     */
    private $random;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param Context $context
     * @param LdapService $ldap
     * @param EntityManagerInterface $em
     * @param Session $session
     * @param Url $url
     * @param callable $random
     * @param Request $request
     */
    public function __construct(
        Context $context,
        Auth $auth,
        EntityManagerInterface $em,
        Session $session,
        Url $url,
        callable $random,
        Request $request
    ) {
        $this->context = $context;
        $this->auth = $auth;
        $this->userRepo = $em->getRepository(User::CLASS);
        $this->em = $em;
        $this->session = $session;
        $this->url = $url;
        $this->random = $random;

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
        if (!$account = $this->auth->authenticate($username, $password)) {
            return $this->context->addContext(['errors' => [self::ERR_AUTH_FAILURE]]);
        }

        $user = $this->userRepo->findOneBy(['handle' => $account['username']]);

        // account disabled manually
        if ($user && !$user->isActive()) {
            return $this->context->addContext(['errors' => [self::ERR_DISABLED]]);
        }

        $isFirstLogin = false;
        if (!$user) {
            $isFirstLogin = true;
            $user = (new User)
                ->withIsActive(true);
        }

        $this->updateUserDetails($account, $user, $isFirstLogin);

        $this->session->clear();
        $this->session->set('user_id', $user->id());
        $this->session->set('is-first-login', $isFirstLogin);

        if ($redirect) {
            $this->url->redirectForURL($redirect);
        } else {
            $this->url->redirectFor('dashboard');
        }
    }

    /**
     * @param array $account
     * @param User $user
     * @param bool $isFirstLogin
     *
     * @return null
     */
    private function updateUserDetails(array $account, User $user, $isFirstLogin)
    {
        if ($isFirstLogin) {
            // generate an id between 100,000,000 - 200,000,000.
            // @todo change id to guid
            $id = \random_int(100000000, 200000000);

            $user
                ->withId($id)
                ->withHandle($account['username']);
        }

        // Always ensure email and name is in sync
        $user
            ->withEmail($account['email'] ?: '')
            ->withName($account['name'] ?: '');

        // Add user settings if not set.
        if (!$user->settings()) {
            $id = call_user_func($this->random);
            $settings = (new UserSettings($id))
                ->withUser($user);

            $this->em->persist($settings);
        }

        $this->em->persist($user);
        $this->em->flush();
    }
}
