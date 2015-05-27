<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Push;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Repository\PushRepository;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

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
    private $repoRepo;
    private $deploymentRepository;

    /**
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @type Response
     */
    private $response;

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
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;

        $this->repoRepo = $em->getRepository(Repository::CLASS);
        $this->deploymentRepository = $em->getRepository(Deployment::CLASS);
        $this->pushRepo = $em->getRepository(Push::CLASS);

        $this->response = $response;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $repo = $this->repoRepo->find($this->parameters['id']);
        $deployment = $this->deploymentRepository->findOneBy([
            'id' => $this->parameters['deployment'],
            'repository' => $repo
        ]);

        if (!$repo || !$deployment) {
            return call_user_func($this->notFound);
        }

        $page = (isset($this->parameters['page'])) ? $this->parameters['page'] : 1;

        // 404, invalid page
        if ($page < 1) {
            return call_user_func($this->notFound);
        }

        $pushes = $this->pushRepo->getByDeployment($deployment, self::MAX_PER_PAGE, ($page-1));

        $total = count($pushes);
        $last = ceil($total / self::MAX_PER_PAGE);

        $rendered = $this->template->render([
            'page' => $page,
            'last' => $last,

            'repo' => $repo,
            'deployment' => $deployment,
            'pushes' => $pushes
        ]);

        $this->response->setBody($rendered);
    }
}
