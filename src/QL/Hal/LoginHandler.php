<?php
namespace QL\Hal;

use MCP\Corp\Account\LdapService;
use QL\Hal\Services\UserService;
use Slim\Http\Response;
use Slim\Http\Request;
use Twig_Template;

/**
 * @api
 */
class LoginHandler
{
    /**
     * @var array
     */
    private $session;

    /**
     * @var LdapService
     */
    private $ldap;

    /**
     * @var Twig_Template
     */
    private $tpl;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @param Session $session
     * @param LdapService $ldap
     * @param Twig_Template $tpl
     * @param UserService $userService
     */
    public function __construct(
        Session $session,
        LdapService $ldap,
        Twig_Template $tpl,
        UserService $userService
    ) {
        $this->session = $session;
        $this->ldap = $ldap;
        $this->tpl = $tpl;
        $this->userService = $userService;
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $username = $request->post('username');
        $password = $request->post('password');

        if (!$username || !$password) {
            $response->body($this->tpl->render(['error' => "must enter username and password"]));
            return;
        }


        $result = $this->ldap->authenticate($username, $password);

        if (!$result) {
            $response->body($this->tpl->render(['error' => 'Authentication failed']));
            return;
        }

        $account = $result;

        $this->session->set('account', $account);

        $userRecord = $this->userService->getById($account->commonId());

        //var_dump($account, $userRecord); die();

        if (empty($userRecord)) {
            $this->userService->create(
                $account->commonId(),
                $account->windowsUsername(),
                $account->email(),
                $account->displayName(),
                $account->badgePhotoUrl()
            );
        } else {
            $this->userService->update(
                $account->commonId(),
                $account->windowsUsername(),
                $account->email(),
                $account->displayName(),
                $account->badgePhotoUrl()
            );
        }

        $response->status(303);
        $response->header('Location', $request->getScheme() . '://' . $request->getHostWithPort() . '/a');
    }
}
