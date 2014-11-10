<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Build;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use MCP\Corp\Account\User;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class BuildsController
{
    const MAX_PER_PAGE = 25;

    /**
     *  @var Twig_Template
     */
    private $template;

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
     *  @var User
     */
    private $user;

    /**
     *  @param Twig_Template $template
     *  @param EntityManager $em
     *  @param RepositoryRepository $repoRepo
     *  @param BuildRepository $buildRepo
     *  @param User $user
     */
    public function __construct(
        Twig_Template $template,
        EntityManager $em,
        RepositoryRepository $repoRepo,
        BuildRepository $buildRepo,
        User $user
    ) {
        $this->template = $template;
        $this->em = $em;
        $this->buildRepo = $buildRepo;
        $this->repoRepo = $repoRepo;
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

        $dql = 'SELECT b FROM QL\Hal\Core\Entity\Build b WHERE b.repository = :repo ORDER BY b.created DESC';
        $query = $this->em->createQuery($dql)
            ->setMaxResults(self::MAX_PER_PAGE)
            ->setFirstResult(self::MAX_PER_PAGE * ($page-1))
            ->setParameter('repo', $repo);
        $builds = $query->getResult();

        if (count($builds) < 1) {
            call_user_func($notFound);
            return;
        }

        $paginator = new Paginator($query);
        $total = count($paginator);
        $last = ceil($total / self::MAX_PER_PAGE);

        $rendered = $this->template->render([
            'repo' => $repo,
            'builds' => $builds,
            'page' => $page,
            'last' => $last,
            'user' => $this->user
        ]);

        $response->setBody($rendered);
    }
}
