<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Build;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Hal\Service\GitHubService;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class StartBuildController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $applicationRepo;

    /**
     * @type EnvironmentRepository
     */
    private $envRepo;

    /**
     * @type GitHubService
     */
    private $github;

    /**
     * @type Request
     */
    private $request;

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
     * @param EntityManagerInterface $em
     * @param GitHubService $github
     * @param Request $request
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        GitHubService $github,
        Request $request,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->github = $github;

        $this->request = $request;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$application = $this->applicationRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        $context = [
            'form' => [
                'environment' => $this->request->post('environment'),
                'search' => $this->request->post('search'),
                'reference' => $this->request->post('reference'),
                'gitref' => $this->request->post('gitref')
            ],

            'application' => $application,
            'branches' => $this->getBranches($application),
            'tags' => $this->getTags($application),
            'open' => $this->getPullRequests($application),
            'closed' => $this->getPullRequests($application, false),
            'environments' => $this->getBuildableEnvironments($application)
        ];

        $this->template->render($context);
    }

    /**
     * @param Application $application
     *
     * @return null
     */
    private function getBuildableEnvironments(Application $application)
    {
        $envs = $this->envRepo->getBuildableEnvironmentsByApplication($application);

        // if empty, throw them a bone with "test"
        if (!$envs) {
            $envs = $this->envRepo->findBy(['key' => 'test']);
        }

        return $envs;
    }

    /**
     * Get an array of branches for an application
     *
     * @param Application $application
     *
     * @return array
     */
    private function getBranches(Application $application)
    {
        $branches = $this->github->branches($application->githubOwner(), $application->githubRepo());

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
     * Get an array of tags for an application
     *
     * @param Application $application
     *
     * @return array
     */
    private function getTags(Application $application)
    {
        $tags = $this->github->tags($application->githubOwner(), $application->githubRepo());

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
     * @param Application $application
     *
     * @return array
     */
    private function getPullRequests(Application $application, $open = true)
    {
        $getter = ($open) ? 'openPullRequests' : 'closedPullRequests';
        $pr = $this->github->$getter($application->githubOwner(), $application->githubRepo());

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
