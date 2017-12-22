<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Auth;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\User;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Github\OAuthHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\MCP\Common\GUID;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;
use QL\Panthor\Session\SessionInterface;

/**
 * NOT CURRENTLY USED
 * NOT CURRENTLY USED
 * NOT CURRENTLY USED
 * NOT CURRENTLY USED
 * NOT CURRENTLY USED
 * NOT CURRENTLY USED
 * NOT CURRENTLY USED
 */
class GithubOAuthController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    const MSG_SUCCESS = 'GitHub token saved.';

    const ERR_TOKEN_EXISTS = 'Your account already has a GitHub token.';
    const ERR_TOKEN_FLAVOR = 'Tokens are immutable. Please remove the token and then re-authorize Hal.';
    const ERR_INVALID_STATE = "Pesky human. Hal is infallible and has prevented your attack.";

    const SESSION_STATE_PARAM = 'github_oauth_state';

    /**
     * Template used in case authorization process blows up
     *
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var OAuthHandler
     */
    private $githubOAuth;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param OAuthHandler $githubOAuth
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        OAuthHandler $githubOAuth,
        URI $uri
    ) {
        $this->template = $template;
        $this->em = $em;
        $this->githubOAuth = $githubOAuth;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $user = $this->getUser($request);
        $session = $this->getSession($request);

        // bomb out if user already has a token
        // if ($user->githubToken()) {
        //     $this->session->flash(static::ERR_TOKEN_EXISTS, 'error', static::ERR_TOKEN_FLAVOR);
        //     return $this->url->redirectFor('settings');
        // }

        $error = $request->getQueryParams()['error'] ?? '';
        $errorDescription = $request->getQueryParams()['error_description'] ?? '';

        // handle error
        if ($error) {
            return $this->handleError($request, $response, $error, $errorDescription);
        }

        $state = $request->getQueryParams()['state'] ?? '';
        $code = $request->getQueryParams()['code'] ?? '';

        // handle redirect back from github
        if ($state && $code) {
            if (!$this->isStateValid($session, $state)) {
                return $this->handleError($request, $response, 'invalid_state', static::ERR_INVALID_STATE);
            }

            if (!$this->saveOAuthGrant($session, $user, $code)) {
                return $this->handleError($request, $response, 'could_not_retrieve_token');
            }

            // Send back to settings
            $this->withFlash($request, Flash::SUCCESS, self::MSG_SUCCESS);
            return $this->withRedirectRoute($response, $this->uri, 'settings');
        }

        // generate and save a oauth state
        $state = GUID::create()->asHex();
        $session->set(self::SESSION_STATE_PARAM, $state);

        [$uri, $query] = $this->githubOAuth->buildOAuthAuthorizationUrl($state);

        return $this->withRedirectAbsoluteURL($response, $uri . '?' . http_build_query($query));
    }

    /**
     * @param SessionInterface $session
     * @param mixed $state
     *
     * @return bool
     */
    private function isStateValid(SessionInterface $session, $state)
    {
        // state missing
        if (!$state) {
            return false;
        }

        // state mismatch
        return ($state === $session->get(self::SESSION_STATE_PARAM));
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $code
     * @param string $description
     *
     * @return ResponseInterface
     */
    private function handleError(ServerRequestInterface $request, ResponseInterface $response, $code, $description = '')
    {
        return $this->withTemplate($request, $response, $this->template, [
            'error_code' => $code,
            'error_description' => $description,
        ]);
    }

    /**
     * @param SessionInterface $session
     * @param User $user
     * @param string $code
     *
     * @return bool
     */
    private function saveOAuthGrant(SessionInterface $session, User $user, $code): bool
    {
        // clear state
        $session->remove(self::SESSION_STATE_PARAM);

        if (!$token = $this->githubOAuth->getOAuthAccessToken($code)) {
            return false;
        }

        // save token to database
        $user->withGithubToken($token);
        $this->em->merge($user);
        $this->em->flush();

        return true;
    }
}
