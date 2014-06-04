<?php

namespace QL\Hal\Controllers\Repository;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Core\Entity\Repository\ServerRepository;
use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;
use MCP\Corp\Account\User;

/**
 *  Rollback Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class RollbackController
{
    private $template;

    private $layout;

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
     *  @var User
     */
    private $user;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param RepositoryRepository $repoRepo
     *  @param ServerRepository $serverRepository
     *  @param EntityManager $em
     *  @param User $user
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        RepositoryRepository $repoRepo,
        ServerRepository $serverRepository,
        EntityManager $em,
        User $user
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->repoRepo = $repoRepo;
        $this->serverRepo = $serverRepository;
        $this->em = $em;
        $this->user = $user;
    }

    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $repo = $this->repoRepo->findOneBy(['id' => $params['id']]);
        $server = $this->serverRepo->findOneBy(['id' => $params['server']]);

        if (!$repo || !$server) {
            call_user_func($notFound);
            return;
        }

        $dql = 'SELECT p FROM QL\Hal\Core\Entity\Push p JOIN p.deployment d WHERE d.server = :server AND d.repository = :repo AND p.status = :status ORDER BY p.end DESC';
        $query = $this->em->createQuery($dql)
            ->setMaxResults(25)
            ->setParameter('repo', $repo)
            ->setParameter('server', $server)
            ->setParameter('status', 'Success');
        $pushes = $query->getResult();

        $response->body(
            $this->layout->render(
                $this->template,
                [
                    'repo' => $repo,
                    'server' => $server,
                    'pushes' => $pushes,
                    'user' => $this->user
                ]
            )
        );
    }
}
