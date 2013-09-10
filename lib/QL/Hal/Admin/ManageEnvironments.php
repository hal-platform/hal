<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use Slim\Http\Response;
use Twig_Template;

/**
 * @api
 */
class ManageEnvironments
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var Twig_Template
     */
    private $tpl;

    /**
     * @param Response $response
     * @param Twig_Template $tpl
     */
    public function __construct(
        Response $response,
        Twig_Template $tpl
    ) {
        $this->response = $response;
        $this->tpl = $tpl;
    }

    public function __invoke()
    {
        $this->response->body($this->tpl->render([]));
    }
}
