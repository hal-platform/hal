<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Group;

use QL\Hal\Core\Entity\Repository\GroupRepository;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class GroupsController
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
     * @param TemplateInterface $template
     * @param GroupRepository $groupRepo
     */
    public function __construct(TemplateInterface $template, GroupRepository $groupRepo)
    {
        $this->template = $template;
        $this->groupRepo = $groupRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $rendered = $this->template->render([
            'groups' => $this->groupRepo->findBy([], ['name' => 'ASC'])
        ]);

        $response->setBody($rendered);
    }
}
