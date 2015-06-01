<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Server;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Type\EnumType\ServerEnum;
use QL\Hal\Services\ElasticBeanstalkService;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ServerController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $serverRepo;
    private $deployRepo;

    /**
     * @type ElasticBeanstalkService
     */
    private $ebService;

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
     * @param EntityManagerInterface $em
     * @param ElasticBeanstalkService $ebService
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        ElasticBeanstalkService $ebService,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;

        $this->serverRepo = $em->getRepository(Server::CLASS);
        $this->deployRepo = $em->getRepository(Deployment::CLASS);

        $this->ebService = $ebService;

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
        if ($server->getType() === ServerEnum::TYPE_EB) {
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

        $this->template->render([
            'server' => $server,
            'deployments_with_optional_eb' => $deployments_with_eb
        ]);
    }
}
