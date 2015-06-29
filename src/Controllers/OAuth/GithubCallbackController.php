<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\OAuth;

use Doctrine\ORM\EntityManagerInterface;
use MCP\DataType\GUID;
use QL\Hal\Core\Entity\User;
use QL\Hal\Github\OAuthHandler;
use QL\Hal\Session;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;

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
        // bomb out if user already has a token
        if ($this->currentUser->githubToken()) {
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
        $this->template->render([
            'error_code' => $code,
            'error_description' => $description,
        ]);
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
        $this->currentUser->withGithubToken($token);
        $this->em->merge($this->currentUser);
        $this->em->flush();

        // Send back to settings
        $this->session->flash(static::SUCCESS_TOKEN_GRANTED, 'success', static::SUCCESS_TOKEN_FLAVOR);
        return $this->url->redirectFor('settings');
    }
}
