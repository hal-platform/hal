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
use QL\Hal\Services\GithubService;
use QL\Hal\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class BuildStartController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type RepositoryRepository
     */
    private $repoRepo;

    /**
     * @type EnvironmentRepository
     */
    private $envRepo;

    /**
     * @type GithubService
     */
    private $github;

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
     * @param RepositoryRepository $repoRepo
     * @param EnvironmentRepository $envRepo
     * @param GithubService $github
     * @param Request $request
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        RepositoryRepository $repoRepo,
        EnvironmentRepository $envRepo,
        GithubService $github,
        Request $request,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->repoRepo = $repoRepo;
        $this->envRepo = $envRepo;
        $this->github = $github;

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

        $context = [
            'form' => [
                'environment' => $this->request->post('environment'),
                'search' => $this->request->post('search'),
                'reference' => $this->request->post('reference'),
                'gitref' => $this->request->post('gitref')
            ],

            'repo' => $repo,
            'branches' => $this->getBranches($repo),
            'tags' => $this->getTags($repo),
            'open' => $this->getPullRequests($repo),
            'closed' => $this->getPullRequests($repo, false),
            'environments' => $this->envRepo->findBy([], ['order' => 'ASC'])
        ];

        $rendered = $this->template->render($context);
        $this->response->setBody($rendered);
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

            return strcasecmp($a['name'], $b['name']);
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

       // $tags = [
       //     ['name' => '3.1'],
       //     ['name' => '3.0'],
       //     ['name' => '3.1.2'],
       //     ['name' => '3.1.1'],
       //     ['name' => '4.0-rc1'],
       //     ['name' => '1.0'],
       //     ['name' => '4.5'],
       //     ['name' => '2'],
       //     ['name' => '4.0-rc2'],
       //     ['name' => '2.5'],
       //     ['name' => '4.0-alpha2'],
       //     ['name' => '2.1.2'],
       //     ['name' => '4.0-beta1'],
       //     ['name' => '4.0-alpha1'],
       //     ['name' => 'a'],
       //     ['name' => 'c'],
       //     ['name' => 'v4.5.1'],
       //     ['name' => 'v3.1'],
       //     ['name' => 'b'],
       //     ['name' => '2.1.1.1'],
       //     ['name' => '14.1'],
       //     ['name' => '4.0'],
       // ];

        usort($tags, $this->tagSorter());

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

        // sort by decreasing pull request number
        usort($pr, function ($a, $b) {
            $a = (int) $a['number'];
            $b = (int) $b['number'];
            return ($a > $b) ? -1 : 1;
        });

        return $pr;
    }

    /**
     * @return callable
     */
    public function tagSorter()
    {
        return function($a, $b) {
            $matchesA = $this->semver($a['name']);
            $matchesB = $this->semver($b['name']);

            // If both are not semver, bump non-semver to bottom
            if ($matchesA === false xor $matchesB === false) {
                if ($matchesA === false) {
                    return 1;
                }

                return -1;
            }

            // both non-semver
            if ($matchesA === false && $matchesB === false) {
                return strcasecmp($a['name'], $b['name']);
            }

            // both sem-ver

            // special case for when version number is the same (1.0-beta1 vs 1.0-alpha1)
            if ($matchesA[1] == $matchesB[1]) {
                $textA = (isset($matchesA[5])) ? $matchesA[5] : '';
                $textB = (isset($matchesB[5])) ? $matchesB[5] : '';
                $numA = (isset($matchesA[6])) ? $matchesA[6] : 0;
                $numB = (isset($matchesB[6])) ? $matchesB[6] : 0;

                // special case when release type is the same (1.0-rc1 vs 1.0-rc2)
                if ($textA == $textB) {
                    return strcasecmp($numB, $numA);
                }

                return strcasecmp($textB, $textA);
            }

            return version_compare($matchesB[1], $matchesA[1]);
        };
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
