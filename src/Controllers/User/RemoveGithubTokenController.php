<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\Entity\User;
use QL\Hal\Github\OAuthHandler;
use QL\Hal\Session;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;

class RemoveGithubTokenController implements ControllerInterface
{
    const SUCCESS_TOKEN_REMOVED = 'GitHub authorization removed.';

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type OAuthHandler
     */
    private $githubOAuth;

    /**
     * @type Session
     */
    private $session;

    /**
     * @type Url
     */
    private $url;

    /**
     * @param TemplateInterface $template
     * @param Request $request
     *
     * @param EntityManagerInterface $em
     * @param User $currentUser
     *
     * @param OAuthHandler $githubOAuth
     * @param Session $session
     * @param Url $url
     */
    public function __construct(
        TemplateInterface $template,
        Request $request,
        EntityManagerInterface $em,
        User $currentUser,
        OAuthHandler $githubOAuth,
        Session $session,
        Url $url
    ) {
        $this->template = $template;
        $this->request = $request;

        $this->em = $em;
        $this->currentUser = $currentUser;

        $this->githubOAuth = $githubOAuth;
        $this->session = $session;
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        // Send back to settings if no token
        if (!$this->currentUser->githubToken()) {
            return $this->url->redirectFor('settings');
        }

        if ($this->request->isPost()) {
            return $this->handlePost();
        }

        $this->template->render();
    }

    /**
     * @return void
     */
    private function handlePost()
    {
        // revoke
        $this->githubOAuth->revokeToken($this->currentUser->githubToken());

        // remove
        $this->currentUser->withGithubToken('');
        $this->em->merge($this->currentUser);
        $this->em->flush();

        // flash
        $this->session->flash(static::SUCCESS_TOKEN_REMOVED, 'success');

        // redirect
        $this->url->redirectFor('settings');
    }
}
