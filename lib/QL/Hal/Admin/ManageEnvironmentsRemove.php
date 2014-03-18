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
 *  Remove an existing environment
 *
 *  @author Bridget Schiefer <BridgetSchiefer@quickenloans.com>
 */
class ManageEnvironmentsRemove
{
    /**
     *  Twig Template
     *
     *  @var Twig_Template
     */
    private $tpl;

    /**
     *  Environment Service
     *
     *  @var EnvironmentService
     */
    private $environment;

    /**
     *
     *  @param Twig_Template $tpl
     *  @param EnvironmentService $environment
     */
    public function __construct(
        Twig_Template $tpl,
        EnvironmentService $environment
    ) {
        $this->tpl = $tpl;
        $this->environment = $environment;
    }

    /**
     *z
     *  @param Request $req
     *  @param Response $res
     *  @param array $params
     *  @param callable $notFound
     *  @return null
     */
    public function __invoke(Request $req, Response $res, array $params = null, callable $notFound = null)
    {
        $id = $params['id'];

        if ($this->validateEnvironment($id)) {
            $this->environment->remove($id);
        }

        $res->status(303);
        $res->header('Location', '/admin/envs');
    }

    /**
     *  Check if an environment id exists
     *
     *  @param string $id
     *  @return bool
     */
    protected function validateEnvironment($id)
    {
        if ($this->environment->getById($id)) {
            return true;
        }

        return false;
    }
}
