<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Build;

use QL\Hal\Core\Repository\BuildRepository;
use QL\Hal\Core\Repository\RepositoryRepository;
use QL\Hal\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\REquest;
use Slim\Http\Response;

class BuildsController implements ControllerInterface
{
    const MAX_PER_PAGE = 25;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type RepositoryRepository
     */
    private $repoRepo;

    /**
     * @type BuildRepository
     */
    private $buildRepo;

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
     * @param EntityManager $em
     * @param RepositoryRepository $repoRepo
     * @param BuildRepository $buildRepo
     * @param Request $request
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        RepositoryRepository $repoRepo,
        BuildRepository $buildRepo,
        Request $request,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->buildRepo = $buildRepo;
        $this->repoRepo = $repoRepo;

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

        $builds = $this->buildRepo->getByRepository($repo, self::MAX_PER_PAGE, ($page-1), $searchFilter);

        $total = count($builds);
        $last = ceil($total / self::MAX_PER_PAGE);

        $rendered = $this->template->render([
            'page' => $page,
            'last' => $last,

            'repo' => $repo,
            'builds' => $builds,
            'search_filter' => $searchFilter
        ]);

        $this->response->setBody($rendered);
    }
}
