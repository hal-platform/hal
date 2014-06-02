<?php

namespace QL\Hal\Controllers\Build;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;
use MCP\Corp\Account\User;

/**
 *  Build Push Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class BuildPushController
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
     *  @var BuildRepository
     */
    private $buildRepo;

    /**
     *  @var User
     */
    private $user;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param EntityManager $em
     *  @param BuildRepository $buildRepo
     *  @param User $user
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        EntityManager $em,
        BuildRepository $buildRepo,
        User $user
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->em = $em;
        $this->buildRepo = $buildRepo;
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
        $build = $this->buildRepo->findOneBy(['id' => $params['build']]);

        if (!$build) {
            call_user_func($notFound);
            return;
        }

        $dql = "SELECT d FROM QL\Hal\Core\Entity\Deployment d JOIN d.server s WHERE d.repository = :repo AND s.environment = :env";
        $query = $this->em->createQuery($dql)
            ->setParameter('repo', $build->getRepository())
            ->setParameter('env', $build->getEnvironment());
        $deployments = $query->getResult();

        $response->body(
            $this->layout->render(
                $this->template,
                [
                    'build' => $build,
                    'deployments' => $deployments,
                    'user' => $this->user
                ]
            )
        );
    }
}
