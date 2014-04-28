<?php

namespace QL\Hal\Controllers\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;

/**
 *  Repository History Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class RepositoryPushHistoryController
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
     *  @var PushRepository
     */
    private $pushRepo;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param EntityManager $em
     *  @param RepositoryRepository $repoRepo
     *  @param PushRepository $pushRepo
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        EntityManager $em,
        RepositoryRepository $repoRepo,
        PushRepository $pushRepo
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->em = $em;
        $this->pushRepo = $pushRepo;
        $this->repoRepo = $repoRepo;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $repo = $this->repoRepo->findOneBy(['key' => $params['repo']]);

        if (!$repo) {
            call_user_func($notFound);
            return;
        }

        //$qb = $this->em->createQueryBuilder();
        //$qb->select('p');
        //$qb->from('QL\Hal\Core\Entity\Push', 'p');
        //$qb->from('QL\Hal\Core\Entity\Deployment', 'd');
        //$qb->where('p.deployment = d');
        //$qb->andWhere('d.repository = :repo');
        //$qb->setParameter('repo', $repo);
        //$qb->orderBy('p.end', 'DESC');



        //$query = $qb->getQuery();
        //$count = $query->get
        //$pushes = $query->getResult();

        $max = 5;
        $page = (int)$params['page'];
        $offset = $max * ($page-1);

        $dql = 'SELECT p FROM QL\Hal\Core\Entity\Push p, QL\Hal\Core\Entity\Deployment d WHERE p.deployment = d AND d.repository = :repo ORDER BY p.end DESC';

        $paginator = new Paginator($dql);
        $paginator
            ->getQuery()
            ->setParameter('repo', $repo)

            ->setFirstResult($offset)
            ->setMaxResults($max);


        $total = count($paginator);
        $pages = ceil($total / $paginator->getMaxResults());

        die(var_dump($total, $pages, $paginator));
    }
}
