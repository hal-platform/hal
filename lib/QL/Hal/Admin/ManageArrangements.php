<?php
namespace QL\Hal\Admin;

use QL\Hal\Services\ArrangementService;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class ManageArrangements
{
    /**
     * @param Twig_Template
     */
    private $tpl;

    /**
     * @param ArrangementService
     */
    private $arr;

    /**
     * @param Twig_Template $tpl
     * @param ArrangementService $arr
     */
    public function __construct(Twig_Template $tpl, ArrangementService $arr)
    {
        $this->tpl = $tpl;
        $this->arr = $arr;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return null
     */
    public function __invoke(Request $req, Response $res)
    {
        $arrList = $this->arr->listAll();
        $res->body($this->tpl->render(['arrangements' => $arrList]));
    }
}
