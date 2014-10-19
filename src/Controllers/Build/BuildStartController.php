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
use Icecave\SemVer\Version;
use Icecave\SemVer\Comparator;

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

        // sort master to top, alpha otherwise
        usort($branches, function ($a, $b) {
            if ($a['name'] == 'master') {
                return -1;
            }
            if ($b['name'] == 'master') {
                return 1;
            }
            return strcmp($a['name'], $b['name']);
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

//        $tags = [
//            ['name' => '3.1'],
//            ['name' => '3.0'],
//            ['name' => '3.1.2'],
//            ['name' => '3.1.1'],
//            ['name' => '4.0-rc1'],
//            ['name' => '1.0'],
//            ['name' => '4.5'],
//            ['name' => '2'],
//            ['name' => '4.0-rc2'],
//            ['name' => '2.5'],
//            ['name' => '4.0-alpha2'],
//            ['name' => '2.1.2'],
//            ['name' => '4.0-beta1'],
//            ['name' => '4.0-alpha1'],
//            ['name' => 'a'],
//            ['name' => 'c'],
//            ['name' => 'v4.5.1'],
//            ['name' => 'v3.1'],
//            ['name' => 'b'],
//            ['name' => '2.1.1.1'],
//            ['name' => '14.1'],
//            ['name' => '4.0'],
//        ];

        $versioned = [];
        $named = [];

        // seperate into versioned tags and named tags
        foreach ($tags as $tag) {
            if ($this->semver($tag['name']) !== false) {
                $versioned[] = $tag;
            } else {
                $named[] = $tag;
            }
        }

        // sort versioned tags according to decreasing version number
        // we're looking for natural sort here, we'll fake it with php version compare
        uasort($versioned, function ($a, $b) {

            $matchesA = $this->semver($a['name']);
            $matchesB = $this->semver($b['name']);

            // special case for when version number is the same (1.0-beta1 vs 1.0-alpha1)
            if ($matchesA[1] == $matchesB[1]) {
                $textA = (isset($matchesA[5])) ? $matchesA[5] : '';
                $textB = (isset($matchesB[5])) ? $matchesB[5] : '';
                $numA = (isset($matchesA[6])) ? $matchesA[6] : 0;
                $numB = (isset($matchesB[6])) ? $matchesB[6] : 0;

                // special case when release type is the same (1.0-rc1 vs 1.0-rc2)
                if ($textA == $textB) {
                    return strcmp($numB, $numA);
                }

                return strcmp($textB, $textA);
            }

            return version_compare($matchesB[1], $matchesA[1]);
        });

        // sort named tags alphabetically
        uasort($named, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return array_merge($versioned, $named);
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

        // sort by decreasing pull request number
        usort($pr, function ($a, $b) {
            return strcmp($b['number'], $a['number']);
        });

        return $pr;
    }

    /**
     * Check to see if a string is *close* to being valid semver. Checking for actual spec compliance turns out to be
     * too restrictive in most cases and won't match what users will expect it to.
     *
     * For example, this will catch things like 1.0, 1, 1.0-rc1, 1.0.0.0, and v1.0 despite those values not being
     * compliant with the semver spec.
     *
     * @param $value
     * @return false|string
     */
    private function semver($value)
    {
        return (preg_match('#^v?(([0-9]+\.?)+)(-(([a-zA-Z]+)([0-9]+)))?#', $value, $matches) > 0) ? $matches : false;
    }
}
