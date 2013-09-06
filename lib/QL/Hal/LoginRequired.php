<?php
namespace QL\Hal;

use Slim\Middleware;

class LoginRequired extends Middleware
{
    /**
     * @var string
     */
    private $loginUrl;

    /**
     * @var array
     */
    private $session;

    /**
     * @param string $loginUrl
     * @param array $session
     */
    public function __construct($loginUrl, array &$session)
    {
        $this->loginUrl = $loginUrl;
        $this->session = &$session;
    }

    /**
     * @return null
     */
    public function call()
    {
        $req = $this->app->request();
        $curUrlPath = $req->getResourceUri();
        $commonid = isset($this->session['commonid']) ? $this->session['commonid'] : null;
        if ($curUrlPath !== $this->loginUrl && is_null($commonid)) {
            $res = $this->app->response();
            $res->status(302);
            $host = $req->getHost();
            $res['Location'] = 'http://' . $host . $this->loginUrl;
            return;
        }
        $this->next->call();
    }
}
