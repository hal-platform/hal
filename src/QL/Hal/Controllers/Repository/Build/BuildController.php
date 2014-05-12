<?php

namespace QL\Hal\Controllers\Repository\Build;

use QL\Hal\Core\Entity\Repository\BuildRepository;
use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;

/**
 *  Build Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class BuildController
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
     *  @var BuildRepository
     */
    private $buildRepo;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param BuildRepository $buildRepo
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        BuildRepository $buildRepo
    ) {
        $this->layout = $layout;
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

        $response->body(
            $this->layout->render(
                $this->template,
                [
                    'build' => $build
                ]
            )
        );
    }
}
