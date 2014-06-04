<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use Doctrine\ORM\EntityManager;
use MCP\Corp\Account\User;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Layout;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 *  Repository Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class RepositoryStatusController
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     *  @var Layout
     */
    private $layout;

    /**
     *  @var EntityManager
     */
    private $em;

    /**
     *  @var RepositoryRepository
     */
    private $repoRepo;

    /**
     *  @var BuildRepository
     */
    private $buildRepo;

    /**
     *  @var PushRepository
     */
    private $pushRepo;

    /**
     *  @var User
     */
    private $user;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param EntityManager $em
     *  @param RepositoryRepository $repoRepo
     *  @param BuildRepository $buildRepo
     *  @param PushRepository $pushRepo
     *  @param User $user
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        EntityManager $em,
        RepositoryRepository $repoRepo,
        BuildRepository $buildRepo,
        PushRepository $pushRepo,
        User $user
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->em = $em;
        $this->repoRepo = $repoRepo;
        $this->buildRepo = $buildRepo;
        $this->pushRepo = $pushRepo;
        $this->user = $user;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $repo = $this->repoRepo->find($params['id']);

        if (!$repo) {
            call_user_func($notFound);
            return;
        }

        $response->body(
            $this->layout->render(
                $this->template,
                [
                    'repo' => $repo,
                    'builds' => $this->getAvailableBuilds($repo),
                    'statuses' => $this->getDeploymentsWithStatus($repo),
                    'user' => $this->user
                ]
            )
        );
    }

    /**
     *  Get the available builds for a given repository
     *
     *  @param Repository $repo
     *  @return Build[]
     */
    private function getAvailableBuilds(Repository $repo)
    {
        $dql = 'SELECT b FROM QL\Hal\Core\Entity\Build b WHERE b.repository = :repo ORDER BY b.status ASC, b.end DESC';
        $query = $this->em->createQuery($dql)
            ->setMaxResults(10)
            ->setParameter('repo', $repo);

        return $query->getResult();
    }

    /**
     *  Get an array of deployments and latest push for each
     *
     *  @param Repository $repo
     *  @return array
     */
    private function getDeploymentsWithStatus(Repository $repo)
    {
        $dql = 'SELECT d FROM QL\Hal\Core\Entity\Deployment d WHERE d.repository = :repo';
        $query = $this->em->createQuery($dql)
            ->setParameter('repo', $repo);
        $deployments = $query->getResult();

        $statuses = [];
        foreach ($deployments as $deploy) {
            // get last attempted push
            $dql = 'SELECT p FROM QL\Hal\Core\Entity\Push p WHERE p.deployment = :deploy ORDER BY p.status ASC, p.end DESC';
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
                'success' => $success
            ];
        }

        return $statuses;
    }
}
