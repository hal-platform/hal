<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Server;

use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use QL\Hal\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class ServerController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type ServerRepository
     */
    private $serverRepo;

    /**
     * @type DeploymentRepository
     */
    private $deployRepo;

    /**
     * @type Response
     */
    private $response;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param TemplateInterface $template
     * @param ServerRepository $serverRepo
     * @param DeploymentRepository $deployRepo
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        ServerRepository $serverRepo,
        DeploymentRepository $deployRepo,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->serverRepo = $serverRepo;
        $this->deployRepo = $deployRepo;

        $this->response = $response;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$server = $this->serverRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        $rendered = $this->template->render([
            'server' => $server,
            'deployments' => $this->deployRepo->findBy(['server' => $server])
        ]);

        $this->response->setBody($rendered);
    }
}

