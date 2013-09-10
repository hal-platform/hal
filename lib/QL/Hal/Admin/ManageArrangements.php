<?php
namespace QL\Hal\Admin;

use QL\Hal\Services\ArrangementService;
use Slim\Http\Response;
use Twig_Template;

class ManageArrangements
{
    /**
     * @param Response
     */
    private $response;

    /**
     * @param Twig_Template
     */
    private $tpl;

    /**
     * @param ArrangementService
     */
    private $arr;

    /**
     * @param Response $response
     * @param Twig_Template $tpl
     * @param ArrangementService $arr
     */
    public function __construct(Response $response, Twig_Template $tpl, ArrangementService $arr)
    {
        $this->response = $response;
        $this->tpl = $tpl;
        $this->arr = $arr;
    }

    /**
     * @return null
     */
    public function __invoke()
    {
        $arrList = $this->arr->listAll();
        $this->arr->body($this->tpl->render(['arrangements' => $arrList]));
    }
}
