<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\OAuth;

use Doctrine\ORM\EntityManager;
use MCP\DataType\GUID;
use QL\Hal\Core\Entity\User;
use QL\Hal\Helpers\GithubOAuthHelper;
use QL\Hal\Session;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;
use Slim\Http\Response;

class GithubCallbackController implements ControllerInterface
{
    const ERR_TOKEN_EXISTS = 'Your account already has a GitHub token.';
    const ERR_TOKEN_FLAVOR = 'Tokens are immutable. Please remove the token and then re-authorize HAL 9000.';

    const SUCCESS_TOKEN_GRANTED = 'GitHub token saved.';
    const SUCCESS_TOKEN_FLAVOR = 'HAL will now attempt to notify github when you push to a project you have github write access for.';

    const ERR_INVALID_STATE = "Pesky human. HAL 9000 is infallible and has prevented your attack.";

    const SESSION_PARAM = 'github_oauth_state';

    /**
     * Template used in case authorization process blows up
     *
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
        // bomb out if user already has a token
        if ($this->currentUser->getGithubToken()) {
            $this->session->flash(static::ERR_TOKEN_EXISTS, 'error', static::ERR_TOKEN_FLAVOR);
            return $this->url->redirectFor('settings');
        }

        // handle error
        if ($this->request->get('error')) {
            return $this->handleError(
                $this->request->get('error'),
                $this->request->get('error_description')
            );
        }

        // handle redirect back from github
        if ($this->request->get('state') && $this->request->get('code')) {
            if (!$this->isStateValid()) {
                return $this->handleError('invalid_state', static::ERR_INVALID_STATE);
            }

            return $this->handleOAuthGrant();
        }

        // generate and save a oauth state
        $state = GUID::create()->asHex();
        $this->session->set(static::SESSION_PARAM, $state);

        list($url, $query) = $this->githubOAuth->buildOAuthAuthorizationUrl($state);
        $this->url->redirectForURL($url, $query);
    }

    /**
     * @return boolean
     */
    private function isStateValid()
    {
        // state missing
        if (!$this->request->get('state')) {
            return false;
        }

        // state mismatch
        return ($this->request->get('state') === $this->session->get(static::SESSION_PARAM));
    }

    /**
     * @param string $code
     * @param string $description
     *
     * @return string
     */
    private function handleError($code, $description)
    {
        $rendered = $this->template->render([
            'error_code' => $code,
            'error_description' => $description,
        ]);

        $this->response->setBody($rendered);
    }

    /**
     * @return string
     */
    private function handleOAuthGrant()
    {
        // clear state
        $this->session->remove(static::SESSION_PARAM);

        if (!$token = $this->githubOAuth->getOAuthAccessToken($this->request->get('code'))) {
            return $this->handleError('could_not_retrieve_token');
        }

        // save token to database
        $this->currentUser->setGithubToken($token);
        $this->entityManager->merge($this->currentUser);
        $this->entityManager->flush();

        // Send back to settings
        $this->session->flash(static::SUCCESS_TOKEN_GRANTED, 'success', static::SUCCESS_TOKEN_FLAVOR);
        return $this->url->redirectFor('settings');
    }

    /**
     * @param string $state
     *
     * @return void
     */
    private function sendToOAuthAuthorization($state)
    {
        $url = rtrim($this->ghBaseUrl, '/') . '/login/oauth/authorize';

        $query = [
            'client_id' => $this->ghClientId,
            'scope' => implode(',', static::$requiredScopes),
            'state' => $state
        ];

        $this->url->redirectForURL($url, $query);
    }
}
