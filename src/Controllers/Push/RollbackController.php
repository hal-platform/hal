<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Push;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Repository\PushRepository;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class RollbackController implements ControllerInterface
{
    const MAX_PER_PAGE = 10;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $applicationRepo;
    private $deploymentRepo;

    /**
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;

        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->deploymentRepo = $em->getRepository(Deployment::CLASS);
        $this->pushRepo = $em->getRepository(Push::CLASS);

        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $application = $this->applicationRepo->find($this->parameters['id']);
        $deployment = $this->deploymentRepo->findOneBy([
            'id' => $this->parameters['deployment'],
            'application' => $application
        ]);

        if (!$application || !$deployment) {
            return call_user_func($this->notFound);
        }

        $page = (isset($this->parameters['page'])) ? $this->parameters['page'] : 1;

        // 404, invalid page
        if ($page < 1) {
            return call_user_func($this->notFound);
        }

        $pushes = $this->pushRepo->getByApplication($application, self::MAX_PER_PAGE, ($page-1));

        $total = count($pushes);
        $last = ceil($total / self::MAX_PER_PAGE);

        $this->template->render([
            'page' => $page,
            'last' => $last,

            'application' => $application,
            'deployment' => $deployment,
            'pushes' => $pushes
        ]);
    }
}
