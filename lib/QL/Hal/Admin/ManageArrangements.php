<?php
namespace QL\Hal\Admin;

use QL\Hal\Layout;
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
     * @var Layout
     */
    private $layout;

    /**
     * @param Twig_Template $tpl
     * @param ArrangementService $arr
     * @param Layout $layout
     */
    public function __construct(
        Twig_Template $tpl,
        ArrangementService $arr,
        Layout $layout
    ) {
        $this->tpl = $tpl;
        $this->arr = $arr;
        $this->layout = $layout;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return null
     */
    public function __invoke(Request $req, Response $res)
    {
        $arrList = $this->arr->listAll();
        $data = ['arrangements' => $arrList];
        $res->setBody($this->layout->renderTemplateWithLayoutData($this->tpl, $data));
    }
}
