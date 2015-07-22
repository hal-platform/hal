<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Push;

use Doctrine\ORM\EntityManagerInterface;
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
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type Application
     */
    private $application;

    /**
     * @type Deployment
     */
    private $deployment;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param NotFound $notFound
     * @param Application $application
     * @param Deployment $deployment
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        NotFound $notFound,
        Application $application,
        Deployment $deployment,
        array $parameters
    ) {
        $this->template = $template;

        $this->pushRepo = $em->getRepository(Push::CLASS);

        $this->notFound = $notFound;
        $this->application = $application;
        $this->deployment = $deployment;

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $page = (isset($this->parameters['page'])) ? $this->parameters['page'] : 1;

        // 404, invalid page
        if ($page < 1) {
            return call_user_func($this->notFound);
        }

        $pushes = $this->pushRepo->getByDeployment($this->deployment, self::MAX_PER_PAGE, ($page-1));

        $total = count($pushes);
        $last = ceil($total / self::MAX_PER_PAGE);

        $this->template->render([
            'page' => $page,
            'last' => $last,

            'application' => $this->application,
            'deployment' => $this->deployment,
            'pushes' => $pushes
        ]);
    }
}
