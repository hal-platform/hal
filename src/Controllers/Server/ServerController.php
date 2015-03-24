<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Server;

use QL\Hal\Core\Repository\DeploymentRepository;
use QL\Hal\Core\Repository\ServerRepository;
use QL\Hal\Core\Type\ServerEnumType;
use QL\Hal\Services\ElasticBeanstalkService;
use QL\Panthor\Slim\NotFound;
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
     * @type ElasticBeanstalkService
     */
    private $ebService;

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
     * @param ElasticBeanstalkService $ebService
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        ServerRepository $serverRepo,
        DeploymentRepository $deployRepo,
        ElasticBeanstalkService $ebService,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->serverRepo = $serverRepo;
        $this->deployRepo = $deployRepo;
        $this->ebService = $ebService;

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

        $deployments = $this->deployRepo->findBy(['server' => $server]);

        // Add status of elastic beanstalk environments
        $ebEnvironments = [];
        if ($server->getType() === ServerEnumType::TYPE_EB) {
            $ebEnvironments = $this->ebService->getEnvironmentsByDeployments($deployments);
        }

        $deployments_with_eb = [];
        foreach ($deployments as $deployment) {
            $dep_eb = [
                'deployment' => $deployment
            ];

            if (isset($ebEnvironments[$deployment->getId()])) {
                $dep_eb['eb_environment'] = $ebEnvironments[$deployment->getId()];
            }

            $deployments_with_eb[] = $dep_eb;
        }

        $rendered = $this->template->render([
            'server' => $server,
            'deployments_with_optional_eb' => $deployments_with_eb
        ]);

        $this->response->setBody($rendered);
    }
}
