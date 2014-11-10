<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Build;

use QL\Hal\Core\Entity\Repository\BuildRepository;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig_Template;

class BuildController
{
    /**
     *  @var Twig_Template
     */
    private $template;

    /**
     *  @var BuildRepository
     */
    private $buildRepo;

    /**
     *  @param Twig_Template $template
     *  @param BuildRepository $buildRepo
     */
    public function __construct(Twig_Template $template, BuildRepository $buildRepo)
    {
        $this->template = $template;
        $this->buildRepo = $buildRepo;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $build = $this->buildRepo->findOneBy(['id' => $params['build']]);

        if (!$build) {
            call_user_func($notFound);
            return;
        }

        $rendered = $this->template->render([
            'build' => $build
        ]);

        $response->setBody($rendered);
    }
}
