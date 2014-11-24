<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Push;

use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class PushController
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @param TemplateInterface $template
     * @param PushRepository $pushRepo
     */
    public function __construct(TemplateInterface $template, PushRepository $pushRepo)
    {
        $this->template = $template;
        $this->pushRepo = $pushRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $push = $this->pushRepo->findOneBy(['id' => $params['push']]);

        if (!$push) {
            return call_user_func($notFound);
        }

        $rendered = $this->template->render([
            'push' => $push
        ]);

        $response->setBody($rendered);
    }
}
