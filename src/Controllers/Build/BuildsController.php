<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Build;

use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class BuildsController
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
     * @param TemplateInterface $template
     * @param EntityManager $em
     * @param RepositoryRepository $repoRepo
     * @param BuildRepository $buildRepo
     */
    public function __construct(
        TemplateInterface $template,
        RepositoryRepository $repoRepo,
        BuildRepository $buildRepo
    ) {
        $this->template = $template;
        $this->buildRepo = $buildRepo;
        $this->repoRepo = $repoRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        if (!$repo = $this->repoRepo->find($params['id'])) {
            return call_user_func($notFound);
        }

        $page = (isset($params['page'])) ? $params['page'] : 1;

        // 404, invalid page
        if ($page < 1) {
            return $notFound();
        }

        $builds = $this->buildRepo->getForRepository($repo, self::MAX_PER_PAGE, ($page-1));

        // 404, no builds
        if (count($builds) < 1) {
            return $notFound();
        }

        // Get current page count
        // Must manually calculate this, as count() will give MAX RESULTS.
        $thisPageCount = 0;
        foreach ($builds as $build) {
            $thisPageCount++;
        }

        // 404, no results on this page
        if ($thisPageCount < 1) {
            return $notFound();
        }

        $total = count($builds);
        $last = ceil($total / self::MAX_PER_PAGE);

        $rendered = $this->template->render([
            'page' => $page,
            'last' => $last,

            'repo' => $repo,
            'builds' => $builds
        ]);

        $response->setBody($rendered);
    }
}
