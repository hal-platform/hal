<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class RepositoryStatusController
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
     * @type RepositoryRepository
     */
    private $repoRepo;

    /**
     * @type BuildRepository
     */
    private $buildRepo;

    /**
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManager $em
     * @param RepositoryRepository $repoRepo
     * @param BuildRepository $buildRepo
     * @param PushRepository $pushRepo
     */
    public function __construct(
        TemplateInterface $template,
        EntityManager $em,
        RepositoryRepository $repoRepo,
        BuildRepository $buildRepo,
        PushRepository $pushRepo
    ) {
        $this->template = $template;
        $this->em = $em;
        $this->repoRepo = $repoRepo;
        $this->buildRepo = $buildRepo;
        $this->pushRepo = $pushRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $repo = $this->repoRepo->find($params['id']);

        if (!$repo) {
            return call_user_func($notFound);
        }

        $builds = $this->buildRepo->findBy(['repository' => $repo], ['created' => 'DESC'], 10);

        $statuses = $this->getDeploymentsWithStatus($repo);

        // seperate by environment
        $envs = [];

        foreach ($statuses as $status) {

            $env = $status['environment']->getKey();

            if (!isset($envs[$env])) {
                $envs[$env] = [];
            }

            $envs[$env][] = $status;
        }

        $rendered = $this->template->render([
            'repo' => $repo,
            'builds' => $builds,
            'statuses' => $statuses,
            'environments' => $envs
        ]);

        $response->setBody($rendered);
    }

    /**
     * Get an array of deployments and latest push for each
     *
     * @param Repository $repo
     * @return array
     */
    private function getDeploymentsWithStatus(Repository $repo)
    {
        $dql = 'SELECT d FROM QL\Hal\Core\Entity\Deployment d JOIN d.server s JOIN s.environment e WHERE d.repository = :repo ORDER BY e.order ASC';
        $query = $this->em->createQuery($dql)
            ->setParameter('repo', $repo);
        $deployments = $query->getResult();

        // sort server by natural name here

        $statuses = [];
        foreach ($deployments as $deploy) {
            // get last attempted push
            $dql = 'SELECT p FROM QL\Hal\Core\Entity\Push p WHERE p.deployment = :deploy ORDER BY p.id DESC';
            $query = $this->em->createQuery($dql)
                ->setMaxResults(1)
                ->setParameter('deploy', $deploy);
            $latest = $query->getOneOrNullResult();

            // get last successful push
            $dql = 'SELECT p FROM QL\Hal\Core\Entity\Push p WHERE p.deployment = :deploy AND p.status = :status ORDER BY p.end DESC';
            $query = $this->em->createQuery($dql)
                ->setMaxResults(1)
                ->setParameter('deploy', $deploy)
                ->setParameter('status', 'Success');
            $success = $query->getOneOrNullResult();

            $statuses[] = [
                'deploy' => $deploy,
                'latest' => $latest,
                'success' => $success,
                'environment' => $deploy->getServer()->getEnvironment()
            ];
        }

        return $statuses;
    }
}
