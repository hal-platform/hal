<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Server;

use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class ServerController
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     *  @var ServerRepository
     */
    private $serverRepo;

    /**
     *  @var DeploymentRepository
     */
    private $deployRepo;

    /**
     *  @param Twig_Template $template
     *  @param ServerRepository $serverRepo
     *  @param DeploymentRepository $deployRepo
     */
    public function __construct(
        Twig_Template $template,
        ServerRepository $serverRepo,
        DeploymentRepository $deployRepo
    ) {
        $this->template = $template;
        $this->serverRepo = $serverRepo;
        $this->deployRepo = $deployRepo;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        if (!$server = $this->serverRepo->find($params['id'])) {
            return $notFound();
        }

        $rendered = $this->template->render([
            'server' => $server,
            'deployments' => $this->deployRepo->findBy(['server' => $server])
        ]);

        $response->setBody($rendered);
    }
}

