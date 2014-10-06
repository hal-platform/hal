<?php

namespace QL\Hal\Controllers\Build;

use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Core\Entity\Repository;
use Twig_Template;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Layout;
use QL\Hal\Services\GithubService;

/**
 *  Build Start Controller
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class BuildStartController
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
     *  @var RepositoryRepository
     */
    private $repoRepo;

    /**
     *  @var EnvironmentRepository
     */
    private $envRepo;

    /**
     *  @var GithubService
     */
    private $github;

    /**
     *  @param Twig_Template $template
     *  @param Layout $layout
     *  @param RepositoryRepository $repoRepo
     *  @param EnvironmentRepository $envRepo
     *  @param GithubService $github
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
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
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
            'pulls' => $this->github->openPullRequests($repo->getGithubUser(), $repo->getGithubRepo()),
            'environments' => $this->envRepo->findBy([], ['order' => 'ASC'])
        ];

        // add closed pull requests if requested in query string
        if ($request->get('with_closed')) {
            $context['closed_pulls'] = $this->github->closedPullRequests($repo->getGithubUser(), $repo->getGithubRepo());
        }

        $response->body($this->layout->render($this->template, $context));
    }

    /**
     *  Get an array of branches for a repository
     *
     *  @param Repository $repo
     *  @return array
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
     *  Get an array of tags for a repository
     *
     *  @param Repository $repo
     *  @return array
     */
    private function getTags(Repository $repo)
    {
        $tags = $this->github->tags(
            $repo->getGithubUser(),
            $repo->getGithubRepo()
        );

        return $tags;
    }
}
