<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\User;
use QL\Hal\Helpers\GithubOAuthHelper;
use QL\Hal\Session;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;
use Slim\Http\Response;

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
     * @type Response
     */
    private $response;

    /**
     * @type EntityManager
     */
    private $entityManager;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type GithubOAuthHelper
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
     * @param Response $response
     *
     * @param EntityManager $entityManager
     * @param User $currentUser
     *
     * @param GithubOAuthHelper $githubOAuth
     * @param Session $session
     * @param Url $url
     */
    public function __construct(
        TemplateInterface $template,
        Request $request,
        Response $response,
        EntityManager $entityManager,
        User $currentUser,
        GithubOAuthHelper $githubOAuth,
        Session $session,
        Url $url
    ) {
        $this->template = $template;
        $this->request = $request;
        $this->response = $response;

        $this->entityManager = $entityManager;
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
        if (!$this->currentUser->getGithubToken()) {
            return $this->url->redirectFor('settings');
        }

        if ($this->request->isPost()) {
            return $this->handlePost();
        }

        $rendered = $this->template->render();
        $this->response->setBody($rendered);
    }

    /**
     * @return void
     */
    private function handlePost()
    {
        // revoke
        $this->githubOAuth->revokeToken($this->currentUser->getGithubToken());

        // remove
        $this->currentUser->setGithubToken('');
        $this->entityManager->merge($this->currentUser);
        $this->entityManager->flush();

        // flash
        $this->session->flash(static::SUCCESS_TOKEN_REMOVED, 'success');

        // redirect
        $this->url->redirectFor('settings');
    }
}
