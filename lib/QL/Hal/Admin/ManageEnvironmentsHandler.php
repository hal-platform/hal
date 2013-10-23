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
     * @var Twig_Template
     */
    private $tpl;

    /**
     * @var EnvironmentService
     */
    private $envService;

    /**
     * @param Twig_Template $tpl
     * @param EnvironmentService $envService
     */
    public function __construct(
        Twig_Template $tpl,
        EnvironmentService $envService
    ) {
        $this->tpl = $tpl;
        $this->envService = $envService;
    }

    public function __invoke(Request $req, Response $res)
    {
        $reorder = $req->get('reorder');
        if ($reorder === '1') {
            $this->handleReOrder($req, $res);
        } else {
            $this->handleCreateEnv($req, $res);
        }
    }

    private function handleCreateEnv(Request $req, Response $res)
    {
        $envname = $req->post('envname');
        $errors = [];

        $this->validateEnvName($envname, $errors);

        if ($errors) {
            $data = [
                'errors' => $errors,
                'cur_env' => $envname,
                'envs' => $this->envService->listAll()
            ];
            $res->body($this->tpl->render($data));
            return;
        }

        $this->envService->create(strtolower($envname));
        $res->status(303);
        $res->header('Location', $req->getScheme() . '://' . $req->getHostWithPort() . '/admin/envs');
    }

    private function validateEnvName($envname, array &$errors)
    {
        if (!preg_match('@^[a-zA-Z_-]*$@', $envname)) {
            $errors[] = 'Environment name must consist of letters, underscores and/or hyphens.';
        }

        if (strlen($envname) > 24 || strlen($envname) < 2) {
            $errors[] = 'Environment name must be between 2 and 24 characters.';
        }
    }

    private function handleReOrder(Request $req, Response $res)
    {
        $data = [];
        foreach ($req->post() as $k => $v) {
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
        $res->status(303);
        $res->header('Location', $req->getScheme() . '://' . $req->getHostWithPort() . '/admin/envs');
    }
}
