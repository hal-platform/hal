<?php

namespace QL\Hal\Controllers;

use Doctrine\ORM\EntityManager;
use QL\Hal\Services\PermissionsService;
use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;
use MCP\Corp\Account\User as LdapUser;

/**
 *  Hello Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class DashboardController
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
     *  @var LdapUser
     */
    private $user;

    /**
     * @var PermissionsService
     */
    private $permissions;

    private $em;

    /**
     * @param Twig_Template $template
     * @param Layout $layout
     * @param LdapUser $user
     * @param PermissionsService $permissions
     * @param EntityManager $em
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        LdapUser $user,
        PermissionsService $permissions,
        EntityManager $em
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->user = $user;
        $this->permissions = $permissions;
        $this->em = $em;
    }

    /**
     *  Run the controller
     *
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        // pending work
        $dql = 'SELECT b, p FROM QL\Hal\Core\Entity\Build b, QL\Hal\Core\Entity\Push p WHERE b.status in (:buildstatus) AND p.status IN (:pushstatus)';
        $query = $this->em->createQuery($dql)
            ->setParameter('buildstatus', ['Waiting', 'Building'])
            ->setParameter('pushstatus', ['Waiting', 'Pushing'])
            ->setMaxResults(25);
        $pending = $query->getResult();

        // user
        $dql = 'SELECT u FROM QL\Hal\Core\Entity\User u WHERE u.id = :id';
        $query = $this->em->createQuery($dql)
            ->setParameter('id', $this->user->commonId());
        $user = $query->getOneOrNullResult();

        // builds
        $dql = 'SELECT b FROM QL\Hal\Core\Entity\Build b WHERE b.user = :user AND b.status IN (:status) ORDER BY b.start DESC';
        $query = $this->em->createQuery($dql)
            ->setParameter('user', $user) // user that will show pushes for front end work ->setParameter('user', 2024851)
            ->setParameter('status', ['Success', 'Error'])
            ->setMaxResults(5);
        $builds = $query->getResult();

        // pushes
        $dql = 'SELECT p FROM QL\Hal\Core\Entity\Push p WHERE p.user = :user AND p.status IN (:status) ORDER BY p.start DESC';
        $query = $this->em->createQuery($dql)
            ->setParameter('user', $user) // user that will show pushes for front end work ->setParameter('user', 2024851)
            ->setParameter('status', ['Success', 'Error'])
            ->setMaxResults(5);
        $pushes = $query->getResult();

        $response->body(
            $this->layout->render(
                $this->template,
                [
                    'user' => $this->user,
                    'repositories' => $this->permissions->userRepositories($this->user),
                    'pending' => $pending,
                    'builds' => $builds,
                    'pushes' => $pushes
                ]
            )
        );
    }
}
