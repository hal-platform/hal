<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Push;

use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Repository\PushRepository;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class PushesController implements ControllerInterface
{
    const MAX_PER_PAGE = 25;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $repoRepo;

    /**
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @type Request
     */
    private $request;

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
     * @param EntityRepository $repoRepo
     * @param PushRepository $pushRepo
     * @param Request $request
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityRepository $repoRepo,
        PushRepository $pushRepo,
        Request $request,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->repoRepo = $repoRepo;
        $this->pushRepo = $pushRepo;

        $this->request = $request;
        $this->response = $response;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$repo = $this->repoRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        $page = (isset($this->parameters['page'])) ? $this->parameters['page'] : 1;
        $searchFilter = is_string($this->request->get('search')) ? $this->request->get('search') : '';

        // 404, invalid page
        if ($page < 1) {
            return call_user_func($this->notFound);
        }

        $pushes = $this->pushRepo->getByRepository($repo, self::MAX_PER_PAGE, ($page-1), $searchFilter);

        $total = count($pushes);
        $last = ceil($total / self::MAX_PER_PAGE);

        $rendered = $this->template->render([
            'page' => $page,
            'last' => $last,

            'repo' => $repo,
            'pushes' => $pushes,
            'search_filter' => $searchFilter
        ]);

        $this->response->setBody($rendered);
    }
}
