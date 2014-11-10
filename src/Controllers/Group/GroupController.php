<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Group;

use QL\Hal\Core\Entity\Repository\GroupRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class GroupController
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     *  @var GroupRepository
     */
    private $groupRepo;

    /**
     *  @var RepositoryRepository
     */
    private $repoRepo;

    /**
     *  @param Twig_Template $template
     *  @param GroupRepository $groupRepo
     *  @param RepositoryRepository $repoRepo
     */
    public function __construct(
        Twig_Template $template,
        GroupRepository $groupRepo,
        RepositoryRepository $repoRepo
    ) {
        $this->template = $template;
        $this->groupRepo = $groupRepo;
        $this->repoRepo = $repoRepo;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        if (!$group = $this->groupRepo->find($params['id'])) {
            return $notFound();
        }

        $rendered = $this->template->render([
            'group' => $group,
            'repos' => $this->repoRepo->findBy(['group' => $group], ['key' => 'ASC'])
        ]);

        $response->body($rendered);
    }
}
