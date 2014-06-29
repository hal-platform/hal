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
class HelloController
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
        $dql = 'SELECT b, p FROM QL\Hal\Core\Entity\Build b, QL\Hal\Core\Entity\Push p WHERE b.status in (:buildstatus) AND p.status in (:pushstatus)';
        $query = $this->em->createQuery($dql)
            ->setParameter('buildstatus', ['Waiting', 'Building', 'Error'])
            ->setParameter('pushstatus', ['Waiting', 'Pushing', 'Error'])
            ->setMaxResults(25);

        $response->body(
            $this->layout->render(
                $this->template,
                [
                    'user' => $this->user,
                    'repos' => $this->permissions->userRepositories($this->user),
                    'pending' => $query->getResult()
                ]
            )
        );
    }
}
