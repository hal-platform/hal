<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Admin;

use QL\Hal\Services\EnvironmentService;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 * @api
 */
class ManageEnvironmentsHandler
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Twig_Template
     */
    private $tpl;

    /**
     * @var EnvironmentService
     */
    private $envService;

    /**
     * @param Response $response
     * @param Request $request
     * @param Twig_Template $tpl
     * @param EnvironmentService $envService
     */
    public function __construct(
        Response $response,
        Request $request,
        Twig_Template $tpl,
        EnvironmentService $envService
    ) {
        $this->response = $response;
        $this->request = $request;
        $this->tpl = $tpl;
        $this->envService = $envService;
    }

    public function __invoke()
    {
        $reorder = $this->request->get('reorder');
        if ($reorder === '1') {
            $this->handleReOrder();
        } else {
            $this->handleCreateEnv();
        }
    }

    private function handleCreateEnv()
    {
        $envname = $this->request->post('envname');
        $errors = [];

        $this->validateEnvName($envname, $errors);

        if ($errors) {
            $this->response->body($this->tpl->render(['errors' => $errors]));
        } else {
            $this->envService->create(strtolower($envname));
            $this->response->status(303);
            $this->response->header('Location', 'http://' . $this->request->getHost() . '/admin/envs');
        }
    }

    private function validateEnvName($envname, array &$errors)
    {
        if (!preg_match('@^[a-zA-Z_-]+$@', $envname)) {
            $errors[] = 'Environment name must consist of letters, underscores and/or hyphens.';
        }

        if (strlen($envname) > 16 || strlen($envname) < 2) {
            $errors[] = 'Environment name must be between 2 and 16 characters.';
        }
    }

    private function handleReOrder()
    {
        $data = [];
        foreach ($this->request->post() as $k => $v) {
            if (substr($k, 0, 3) !== 'env') {
                continue;
            }
            $id = (int)substr($k, 3);
            if ($id === 0) {
                continue;
            }
            $v = (int)$v;
            if ($v === 0) {
                continue;
            }
            $data[$id] = $v;
        }
        $this->envService->updateOrder($data);
        $this->response->status(303);
        $this->response->header('Location', 'http://' . $this->request->getHost() . '/admin/envs');
    }
}
