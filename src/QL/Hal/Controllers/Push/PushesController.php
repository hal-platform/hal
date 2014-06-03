<?php

namespace QL\Hal\Controllers\Push;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;

/**
 *  Repository Push History Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class PushesController
{
    const MAX_PER_PAGE = 25;

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
        $repo = $this->repoRepo->findOneBy(['id' => $params['id']]);

        if (!$repo) {
            call_user_func($notFound);
            return;
        }

        $page = (isset($params['page'])) ? $params['page'] : 1;

        if ($page < 1) {
            call_user_func($notFound);
            return;
        }

        $dql = 'SELECT p FROM QL\Hal\Core\Entity\Push p JOIN p.deployment d WHERE d.repository = :repo ORDER BY p.end DESC';
        $query = $this->em->createQuery($dql)
            ->setMaxResults(self::MAX_PER_PAGE)
            ->setFirstResult(self::MAX_PER_PAGE * ($page-1))
            ->setParameter('repo', $repo);
        $pushes = $query->getResult();

        if (count($pushes) < 1) {
            call_user_func($notFound);
            return;
        }

        $paginator = new Paginator($query);
        $total = count($paginator);
        $last = ceil($total / self::MAX_PER_PAGE);

        $response->body(
            $this->layout->render(
                $this->template,
                [
                    'repo' => $repo,
                    'pushes' => $pushes,
                    'page' => $page,
                    'last' => $last
                ]
            )
        );
    }
}
