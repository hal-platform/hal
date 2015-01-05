<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Push;

use QL\Hal\Core\Entity\Repository\PushRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class PushesController
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
     * @type PushRepository
     */
    private $pushRepo;

    /**
     * @param TemplateInterface $template
     * @param RepositoryRepository $repoRepo
     * @param PushRepository $pushRepo
     */
    public function __construct(
        TemplateInterface $template,
        RepositoryRepository $repoRepo,
        PushRepository $pushRepo
    ) {
        $this->template = $template;
        $this->repoRepo = $repoRepo;
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
        if (!$repo = $this->repoRepo->find($params['id'])) {
            return call_user_func($notFound);
        }

        $page = (isset($params['page'])) ? $params['page'] : 1;

        // 404, invalid page
        if ($page < 1) {
            return $notFound();
        }

        $pushes = $this->pushRepo->getForRepository($repo, self::MAX_PER_PAGE, ($page-1));

        // 404, no pushes
        if (count($pushes) < 1) {
            return $notFound();
        }

        // Get current page count
        // Must manually calculate this, as count() will give MAX RESULTS.
        $thisPageCount = 0;
        foreach ($pushes as $push) {
            $thisPageCount++;
        }

        // 404, no results on this page
        if ($thisPageCount < 1) {
            return $notFound();
        }

        $total = count($pushes);
        $last = ceil($total / self::MAX_PER_PAGE);

        $rendered = $this->template->render([
            'repo' => $repo,
            'pushes' => $pushes,
            'page' => $page,
            'last' => $last
        ]);

        $response->setBody($rendered);
    }
}
