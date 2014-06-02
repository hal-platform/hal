<?php

namespace QL\Hal\Controllers\Push;

use QL\Hal\Core\Entity\Repository\PushRepository;
use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;

/**
 *  Push Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class PushController
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
     *  @var PushRepository
     */
    private $pushRepo;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param PushRepository $pushRepo
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        PushRepository $pushRepo
    ) {
        $this->layout = $layout;
        $this->template = $template;
        $this->pushRepo = $pushRepo;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $push = $this->pushRepo->findOneBy(['id' => $params['push']]);

        if (!$push) {
            call_user_func($notFound);
            return;
        }

        $response->body(
            $this->layout->render(
                $this->template,
                [
                    'push' => $push
                ]
            )
        );
    }
}
