<?php
namespace QL\GitBert2;

use Slim\Http\Response;
use Twig_TemplateInterface;

class GBAdminMain 
{
    /**
     * @param Response
     */
    private $response;

    /**
     * @param Twig_TemplateInterface
     */
    private $tpl;

    /**
     * @param Response $response
     * @param Twig_TemplateInterface $tpl
     */
    public function __construct(Response $response, Twig_TemplateInterface $tpl)
    {
        $this->response = $response;
        $this->tpl = $tpl;
    }

    /**
     * @return null
     */
    public function __invoke()
    {
        $this->response->body($this->tpl->render([]));
    }
}
