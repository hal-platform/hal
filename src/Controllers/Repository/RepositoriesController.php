<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use QL\Hal\Core\Entity\Repository\RepositoryRepository;
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
     *  @var RepositoryRepository
     */
    private $repoRepo;

    /**
     *  @param Twig_Template $template
     *  @param RepositoryRepository $repoRepo
     */
    public function __construct(Twig_Template $template, RepositoryRepository $repoRepo)
    {
        $this->template = $template;
        $this->repoRepo = $repoRepo;
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
        $rendered = $this->template->render([
            'repos' => $this->repoRepo->findBy([], ['key' => 'ASC'])
        ]);

        $response->body($rendered);
    }
}
