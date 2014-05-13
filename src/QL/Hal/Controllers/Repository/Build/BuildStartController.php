<?php

namespace QL\Hal\Controllers\Repository\Build;

use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
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
    private $template;

    private $layout;

    private $repoRepo;

    private $github;

    public function __construct(
        Twig_Template $template,
        Layout $layout,
        RepositoryRepository $repoRepo,
        GithubService $github
    ) {
        $this->template = $template;
        $this->layout = $layout;
        $this->repoRepo = $repoRepo;
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
        $repo = $this->repoRepo->findOneBy(['key' => $params['repo']]);

        if (!$repo) {
            call_user_func($notFound);
            return;
        }

        die(var_dump([
            'repo' => $repo,
             'ghuser' => $repo->getGithubUser(),
             'ghrepo' => $repo->getGithubRepo(),
            'branches' => $this->github->branches($repo->getGithubUser(), $repo->getGithubRepo()),
            'tags' => $this->github->tags($repo->getGithubUser(), $repo->getGithubRepo()),
                'pulls' => $this->github->openPullRequests($repo->getGithubUser(), $repo->getGithubRepo())
        ]));

        $response->body(
            $this->layout->render(
                $this->template,
                [
                    'repo' => $repo,
                    'branches' => $this->github->branches($repo->getGithubUser(), $repo->getGithubRepo()),
                    'tags' => $this->github->tags($repo->getGithubUser(), $repo->getGithubRepo())
                ]
            )
        );
    }
}
