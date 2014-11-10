<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Environment;

use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class AdminReorderController
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     *  @var EnvironmentRepository
     */
    private $envRepo;

    /**
     *  @param Twig_Template $template
     *  @param EnvironmentRepository $envRepo
     */
    public function __construct(Twig_Template $template, EnvironmentRepository $envRepo)
    {
        $this->template = $template;
        $this->envRepo = $envRepo;
    }

    /**
     *  Run the controller
     *
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        if (!$environments = $this->envRepo->findBy([], ['order' => 'ASC'])) {
            return $notFound();
        }

        $rendered = $this->template->render([
            'envs' => $environments
        ]);

        $response->setBody($rendered);
    }
}
