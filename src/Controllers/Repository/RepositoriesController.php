<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use QL\Hal\Core\Entity\Repository\GroupRepository;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class RepositoriesController
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
     *  @param Twig_Template $template
     *  @param GroupRepository $groupRepo
     */
    public function __construct(Twig_Template $template, GroupRepository $groupRepo)
    {
        $this->template = $template;
        $this->groupRepo = $groupRepo;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $rendered = $this->template->render([
            'groups' => $this->groupRepo->findBy([], ['name' => 'ASC'])
        ]);

        $response->setBody($rendered);
    }
}
