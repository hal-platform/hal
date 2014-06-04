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
                    'pushes' => $this->getPushesForRepository($repo),
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
        $qb = $this->em->createQueryBuilder();
        $qb->select('b');
        $qb->from('QL\Hal\Core\Entity\Build', 'b');
        //$qb->where('b.status != :status');
        //$qb->setParameter('status', 'Removed');
        $qb->where('b.repository = :repo');
        $qb->setParameter('repo', $repo);
        $qb->orderBy('b.start', 'DESC');
        $qb->setMaxResults(10);

        return $qb->getQuery()->getResult();
    }

    /**
     *  Get an array of deployments and latest push for each
     *
     *  @param Repository $repo
     *  @return array
     */
    private function getDeploymentsWithStatus(Repository $repo)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('d');
        $qb->from('QL\Hal\Core\Entity\Deployment', 'd');
        $qb->where('d.repository = :repo');
        $qb->setParameter('repo', $repo);

        $statuses = [];
        foreach ($qb->getQuery()->getResult() as $deploy) {
            // get last attempted push
            $qb = $this->em->createQueryBuilder();
            $qb->select('p');
            $qb->from('QL\Hal\Core\Entity\Push', 'p');
            $qb->where('p.deployment = :deploy');
            $qb->orderBy('p.status', 'ASC');
            $qb->addOrderBy('p.end', 'DESC');
            $qb->setParameter('deploy', $deploy);
            $qb->setMaxResults(1);
            $latest = $qb->getQuery()->getOneOrNullResult();

            // get last successful push
            $qb = $this->em->createQueryBuilder();
            $qb->select('p');
            $qb->from('QL\Hal\Core\Entity\Push', 'p');
            $qb->where('p.deployment = :deploy');
            $qb->setParameter('deploy', $deploy);
            $qb->andWhere('p.status = :status');
            $qb->setParameter('status', 'Success');
            $qb->addOrderBy('p.end', 'DESC');
            $qb->setMaxResults(1);
            $success = $qb->getQuery()->getOneOrNullResult();

            $statuses[] = [
                'deploy' => $deploy,
                'latest' => $latest,
                'success' => $success
            ];
        }

        return $statuses;
    }

    /**
     *  Get an array of pushes for a given repository
     *
     *  @param Repository $repo
     *  @return array
     */
    private function getPushesForRepository(Repository $repo)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('p');
        $qb->from('QL\Hal\Core\Entity\Push', 'p');
        $qb->from('QL\Hal\Core\Entity\Build', 'b');
        $qb->from('QL\Hal\Core\Entity\Repository', 'r');
        $qb->where('p.build = b');
        $qb->andWhere('b.repository = r');
        $qb->andWhere('r = :repo');
        $qb->setParameter('repo', $repo);
        $qb->orderBy('p.end', 'DESC');
        $qb->setMaxResults(10);

        return $qb->getQuery()->getResult();
    }

}
