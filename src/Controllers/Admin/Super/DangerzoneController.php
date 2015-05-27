<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Admin\Super;

use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class DangerzoneController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type Response
     */
    private $response;

    /**
     * @param TemplateInterface $template
     * @param Request $request
     * @param Response $response
     */
    public function __construct(TemplateInterface $template, Request $request, Response $response)
    {
        $this->template = $template;

        $this->request = $request;
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if ($this->request->isPost()) {
            return $this->handlePost();
        }

        $rendered = $this->template->render();
        $this->response->setBody($rendered);
    }

    /**
     * @return void
     */
    private function handlePost()
    {
        $cmd = $this->request->post('cmd');

        exec($cmd, $rawOutput, $exitCode);
        $output = implode("\n", $rawOutput);

        $encoded = json_encode([
            'cmd' => $_POST['cmd'],
            'output' => $output,
            'exit' => $exitCode,
        ]);

        $this->response->setBody($encoded);
        $this->response->headers->set('Content-Type', 'application/json');
    }
}
