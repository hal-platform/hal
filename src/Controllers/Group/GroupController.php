<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Group;

use QL\Hal\Core\Repository\GroupRepository;
use QL\Hal\Core\Repository\RepositoryRepository;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class GroupController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type GroupRepository
     */
    private $groupRepo;

    /**
     * @type RepositoryRepository
     */
    private $repoRepo;

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
     * @param GroupRepository $groupRepo
     * @param RepositoryRepository $repoRepo
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        GroupRepository $groupRepo,
        RepositoryRepository $repoRepo,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->groupRepo = $groupRepo;
        $this->repoRepo = $repoRepo;

        $this->response = $response;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$group = $this->groupRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        $rendered = $this->template->render([
            'group' => $group,
            'repos' => $this->repoRepo->findBy(['group' => $group], ['key' => 'ASC'])
        ]);

        $this->response->setBody($rendered);
    }
}
