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

    public function __invoke(Request $request, Response $response)
    {
        $username = $request->post('username');
        $password = $request->post('password');

        if (!$username || !$password) {
            $response->body($this->tpl->render(['error' => "must enter username and password"]));
            return;
        }

        $error = $this->ldap->authenticate('MI\\' . $username, $password);
        if ($error !== '') {
            $response->body($this->tpl->render(['error' => $error]));
            return;
        }

        $account = $this->ldap->searchByUsername($username);

        // there probably should be error checking here...

        $commonId = $account['extensionattribute8'];
        $picture = $account['extensionattribute6'];
        $this->session->set('commonid', $commonId);
        $this->session->set('account', $account);

        $userRecord = $this->userService->getById($commonId);
        if (empty($userRecord)) {
            $this->userService->create($commonId, $account['samaccountname'], $account['mail'], $account['displayname'], $picture);
        } else {
            $this->userService->update($commonId, $account['samaccountname'], $account['mail'], $account['displayname'], $picture);
        }

        $response->status(302);
        $response['Location'] = $request->getScheme() . '://' . $request->getHostWithPort() . '/a';
    }
}
