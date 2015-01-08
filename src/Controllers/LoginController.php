<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers;

use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class LoginController implements ControllerInterface
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
     * @param TemplateInterface $template
     * @param Response $response
     */
    public function __construct(TemplateInterface $template, Response $response)
    {
        $this->template = $template;
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $rendered = $this->template->render();

        $this->response->setBody($rendered);
    }
}
