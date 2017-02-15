<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Github\OAuthHandler;
use Hal\UI\Session;
use QL\Hal\Core\Entity\User;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;

class RemoveGithubTokenController implements ControllerInterface
{
    const SUCCESS_TOKEN_REMOVED = 'GitHub authorization removed.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var User
     */
    private $currentUser;

    /**
     * @var OAuthHandler
     */
    private $githubOAuth;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Url
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
     * @inheritDoc
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
