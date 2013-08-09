<?php
namespace QL\GitBert2;

use MCP\Corp\Account\LdapService;
use QL\GitBert2\Services\UserService;
use Slim\Http\Response;
use Slim\Http\Request;
use Twig_Template;

class GBLoginHandler
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var Request
     */
    private $request;

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
     * @param Response $response
     * @param Request $request
     * @param array $session
     * @param LdapService $ldap
     * @param Twig_Template $tpl
     * @param UserService $userService
     */
    public function __construct(
        Response $response,
        Request $request,
        array &$session,
        LdapService $ldap,
        Twig_Template $tpl,
        UserService $userService
    ) {
        $this->response = $response;
        $this->request = $request;
        $this->session = &$session;
        $this->ldap = $ldap;
        $this->tpl = $tpl;
        $this->userService = $userService;
    }

    public function __invoke()
    {
        $username = $this->request->post('username');
        $password = $this->request->post('password');

        if (!$username || !$password) {
            $this->response->body($this->tpl->render(['error' => "must enter username and password"]));
            return;
        }

        $error = $this->ldap->authenticate('MI\\' . $username, $password);
        if ($error !== '') {
            $this->response->body($this->tpl->render(['error' => $error]));
            return;
        }

        $account = $this->ldap->searchByUsername($username);

        // there probably should be error checking here...

        $commonId = $account['extensionattribute8'];
        $picture = $account['extensionattribute6'];
        $this->session['commonid'] = $commonId;
        $this->session['account'] = $account;

        $userRecord = $this->userService->getById($commonId);
        if (empty($userRecord)) {
            $this->userService->create($commonId, $account['samaccountname'], $account['mail'], $account['displayname'], $picture);
        } else {
            $this->userService->update($commonId, $account['samaccountname'], $account['mail'], $account['displayname'], $picture);
        }

        $this->response->status(302);
        $this->response['Location'] = 'http://' . $this->request->getHost() . '/';
    }
}
