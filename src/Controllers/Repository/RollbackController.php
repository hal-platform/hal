<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class RollbackController
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     *  @var RepositoryRepository
     */
    private $repoRepo;

    /**
     *  @var ServerRepository
     */
    private $serverRepo;

    /**
     *  @var EntityManager
     */
    private $em;

    /**
     *  @param Twig_Template $template
     *  @param RepositoryRepository $repoRepo
     *  @param ServerRepository $serverRepository
     *  @param EntityManager $em
     */
    public function __construct(
        Twig_Template $template,
        RepositoryRepository $repoRepo,
        ServerRepository $serverRepository,
        EntityManager $em
    ) {
        $this->template = $template;
        $this->repoRepo = $repoRepo;
        $this->serverRepo = $serverRepository;
        $this->em = $em;
    }

    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $repo = $this->repoRepo->find($params['id']);
        $server = $this->serverRepo->find($params['server']);

        if (!$repo || !$server) {
            return call_user_func($notFound);
        }

        $dql = 'SELECT p FROM QL\Hal\Core\Entity\Push p
            JOIN p.deployment d JOIN p.build b WHERE d.server = :server AND d.repository = :repo AND p.status = :status AND b.status = :buildstatus ORDER BY p.end DESC';
        $query = $this->em->createQuery($dql)
            ->setMaxResults(25)
            ->setParameter('repo', $repo)
            ->setParameter('server', $server)
            ->setParameter('status', 'Success')
            ->setParameter('buildstatus', 'Success');
        $pushes = $query->getResult();

        $rendered = $this->template->render([
            'repo' => $repo,
            'server' => $server,
            'pushes' => $pushes
        ]);
    }
}
