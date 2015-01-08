<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin;

use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class DashboardController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Response
     */
    private $response;

    /**
     * @type int
     */
    private $statusCode;

    /**
     * @param TemplateInterface $template
     * @param int $statusCode
     * @param Response $response
     */
    public function __construct(TemplateInterface $template, Response $response, $statusCode = 200)
    {
        $this->template = $template;
        $this->statusCode = $statusCode;

        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $rendered = $this->template->render();

        $this->response->setStatus($this->statusCode);
        $this->response->setBody($rendered);
    }
}
