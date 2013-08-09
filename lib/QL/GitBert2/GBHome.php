<?php
namespace QL\GitBert2;

use Slim\Http\Response;
use Twig_Template;

class GBHome 
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var Twig_TemplateInterface
     */
    private $tpl;

    /**
     * @var array
     */
    private $session;

    /**
     * @param Response $response
     * @param Twig_Template $tpl
     * @param array $session
     */
    public function __construct(Response $response, Twig_Template $tpl, array &$session)
    {
        $this->response = $response;
        $this->tpl = $tpl;
        $this->session = &$session;
    }

    /**
     * @return null
     */
    public function __invoke()
    {
        $this->response->body($this->tpl->render(['account' => $this->session['account']]));
    }
}
