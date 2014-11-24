<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Push;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\Repository\DeploymentRepository;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class PushStartController
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @type BuildRepository
     */
    private $buildRepo;

    /**
     * @type DeploymentRepository
     */
    private $deploymentRepo;

    /**
     * @type ServerRepository
     */
    private $serverRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManager $em
     * @param BuildRepository $buildRepo
     * @param DeploymentRepository $deploymentRepo
     * @param ServerRepository $serverRepo
     */
    public function __construct(
        TemplateInterface $template,
        EntityManager $em,
        BuildRepository $buildRepo,
        DeploymentRepository $deploymentRepo,
        ServerRepository $serverRepo
    ) {
        $this->template = $template;
        $this->em = $em;
        $this->buildRepo = $buildRepo;
        $this->deploymentRepo = $deploymentRepo;
        $this->serverRepo = $serverRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $build = $this->buildRepo->findOneBy(['id' => $params['build']]);

        if (!$build || $build->getStatus() != 'Success') {
            return call_user_func($notFound);
        }

        $deployments = $this->getDeploymentsForBuild($build);

        $statuses = [];
        foreach ($deployments as $deployment) {

            $latest = $this->getLastPush($deployment);
            if ($latest && $latest->getStatus() === 'Success') {
                $success = $latest;
            } else {
                $success = $this->getLastSuccessfulPush($deployment);
            }

            $statuses[] = [
                'deployment' => $deployment,
                'latest' => $latest,
                'success' => $success
            ];
        }

        $rendered = $this->template->render([
            'build' => $build,
            'selected' => $request->get('deployment'),
            'statuses' => $statuses
        ]);

        $response->setBody($rendered);
    }

    /**
     * Get the deployments a build can be deployed to.
     *
     * @todo Move to repository
     *
     * @param Build $build
     *
     * @return Deployments[]
     */
    private function getDeploymentsForBuild(Build $build)
    {
        $servers = $this->serverRepo->findBy(['environment' => $build->getEnvironment()]);

        $criteria = [
            'repository' => $build->getRepository(),
            'server' => $servers
        ];

        return $this->deploymentRepo->findBy($criteria, ['server' => 'ASC']);
    }

    /**
     * Get the last push for a given deployment.
     *
     * @todo Move to repository
     *
     * @param Deployment $deployment
     *
     * @return Push|null
     */
    private function getLastPush(Deployment $deployment)
    {
        $dql = 'SELECT p FROM QL\Hal\Core\Entity\Push p WHERE p.deployment = :deploy ORDER BY p.created DESC';
        $query = $this->em->createQuery($dql)
            ->setMaxResults(1)
            ->setParameter('deploy', $deployment);

        return $query->getOneOrNullResult();
    }

    /**
     * Get the last successful push for a given deployment.
     *
     * @todo Move to repository
     *
     * @param Deployment $deployment
     *
     * @return Push|null
     */
    private function getLastSuccessfulPush(Deployment $deployment)
    {
        // get last successful push
        $dql = 'SELECT p FROM QL\Hal\Core\Entity\Push p WHERE p.deployment = :deploy AND p.status = :status ORDER BY p.created DESC';
        $query = $this->em->createQuery($dql)
                          ->setMaxResults(1)
                          ->setParameter('deploy', $deployment)
                          ->setParameter('status', 'Success');

        return $query->getOneOrNullResult();
    }
}
