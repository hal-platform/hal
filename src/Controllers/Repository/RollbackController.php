<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use Doctrine\ORM\EntityManager;
use MCP\Corp\Account\User as LdapUser;
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
     *  @var LdapUser
     */
    private $user;

    /**
     *  @param Twig_Template $template
     *  @param RepositoryRepository $repoRepo
     *  @param ServerRepository $serverRepository
     *  @param EntityManager $em
     *  @param LdapUser $user
     */
    public function __construct(
        Twig_Template $template,
        RepositoryRepository $repoRepo,
        ServerRepository $serverRepository,
        EntityManager $em,
        LdapUser $user
    ) {
        $this->template = $template;
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
            'pushes' => $pushes,
            'user' => $this->user
        ]);
    }
}
