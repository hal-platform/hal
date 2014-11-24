<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Push;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class PushesController
{
    const MAX_PER_PAGE = 25;

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
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManager $em
     * @param RepositoryRepository $repoRepo
     * @param PushRepository $pushRepo
     */
    public function __construct(
        TemplateInterface $template,
        EntityManager $em,
        RepositoryRepository $repoRepo,
        PushRepository $pushRepo
    ) {
        $this->template = $template;
        $this->em = $em;
        $this->pushRepo = $pushRepo;
        $this->repoRepo = $repoRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $repo = $this->repoRepo->findOneBy(['id' => $params['id']]);

        if (!$repo) {
            return call_user_func($notFound);
        }

        $page = (isset($params['page'])) ? $params['page'] : 1;

        if ($page < 1) {
            call_user_func($notFound);
            return;
        }

        $dql = 'SELECT p FROM QL\Hal\Core\Entity\Push p JOIN p.deployment d WHERE d.repository = :repo ORDER BY p.created DESC';
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

        $rendered = $this->template->render([
            'repo' => $repo,
            'pushes' => $pushes,
            'page' => $page,
            'last' => $last
        ]);

        $response->setBody($rendered);
    }
}
