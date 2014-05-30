<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Layout;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

/**
 *  Repositories Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class RepositoriesController
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
     *  @var RepositoryRepository
     */
    private $repoRepo;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param RepositoryRepository $repoRepo
     */
    public function __construct(Twig_Template $template, Layout $layout, RepositoryRepository $repoRepo)
    {
        $this->template = $template;
        $this->layout = $layout;
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
        $rendered = $this->layout->render($this->template, [
            'repos' => $this->repoRepo->findAll()
        ]);

        $response->body($rendered);
    }
}
