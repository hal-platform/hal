<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Push;

use QL\Hal\Core\Repository\PushRepository;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

class PushController implements ControllerInterface
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
     * @type Response
     */
    private $response;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param TemplateInterface $template
     * @param PushRepository $pushRepo
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        PushRepository $pushRepo,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->pushRepo = $pushRepo;

        $this->response = $response;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$push = $this->pushRepo->find($this->parameters['push'])) {
            return call_user_func($this->notFound);
        }

        $rendered = $this->template->render([
            'push' => $push
        ]);

        $this->response->setBody($rendered);
    }
}
