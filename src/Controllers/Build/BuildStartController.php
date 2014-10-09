<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Build;

use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Core\Entity\Repository;
use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;
use QL\Hal\Services\GithubService;

class BuildStartController
{
    /**
     * @var Twig_Template
     */
    private $template;

    /**
     * @var Layout
     */
    private $layout;

    /**
     * @var RepositoryRepository
     */
    private $repoRepo;

    /**
     * @var EnvironmentRepository
     */
    private $envRepo;

    /**
     * @var GithubService
     */
    private $github;

    /**
     * @param Twig_Template $template
     * @param Layout $layout
     * @param RepositoryRepository $repoRepo
     * @param EnvironmentRepository $envRepo
     * @param GithubService $github
     */
    public function __construct(
        Twig_Template $template,
        Layout $layout,
        RepositoryRepository $repoRepo,
        EnvironmentRepository $envRepo,
        GithubService $github
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->repoRepo = $repoRepo;
        $this->envRepo = $envRepo;
        $this->github = $github;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        if (!$repo = $this->repoRepo->findOneBy(['id' => $params['id']])) {
            return call_user_func($notFound);
        }

        $context = [
            'repoId' => $params['id'],
            'repo' => $repo,
            'branches' => $this->getBranches($repo),
            'tags' => $this->getTags($repo),
            'pulls' => $this->getPullRequests($repo),
            'closed_pulls' => $this->getPullRequests($repo, false),
            'environments' => $this->envRepo->findBy([], ['order' => 'ASC'])
        ];

        $response->body($this->layout->render($this->template, $context));
    }

    /**
     * Get an array of branches for a repository
     *
     * @param Repository $repo
     * @return array
     */
    private function getBranches(Repository $repo)
    {
        $branches = $this->github->branches(
            $repo->getGithubUser(),
            $repo->getGithubRepo()
        );

        // sort master to top, rest alphabetically
        usort($branches, function ($a, $b) {
            if ($a['name'] == 'master') {
                return -1;
            }
            if ($b['name'] == 'master') {
                return 1;
            }
            return ($a['name'] > $b['name']);
        });

        return $branches;
    }

    /**
     * Get an array of tags for a repository
     *
     * @param Repository $repo
     * @return array
     */
    private function getTags(Repository $repo)
    {
        $tags = $this->github->tags(
            $repo->getGithubUser(),
            $repo->getGithubRepo()
        );

        return $tags;
    }

    /**
     * Get pull requests, sort in descending order by number.
     *
     * @param Repository $repo
     * @return array
     */
    private function getPullRequests(Repository $repo, $open = true)
    {
        $getter = ($open) ? 'openPullRequests' : 'closedPullRequests';
        $pr = $this->github->$getter(
            $repo->getGithubUser(),
            $repo->getGithubRepo()
        );

        usort($pr, function ($a, $b) {
            return ($a['number'] < $b['number']);
        });

        return $pr;
    }
}
