<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Build;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Hal\Service\GitHubService;
use QL\Hal\Utility\ReleaseSortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class StartBuildController implements ControllerInterface
{
    use ReleaseSortingTrait;

    /**
     * @type TemplateInterface
     */
    private $template;

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
     * @type Application
     */
    private $application;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param GitHubService $github
     * @param Request $request
     * @param Application $application
     * @param User $currentUser
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        GitHubService $github,
        Request $request,
        Application $application,
        User $currentUser
    ) {
        $this->template = $template;
        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->github = $github;

        $this->request = $request;
        $this->application = $application;
        $this->currentUser = $currentUser;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $context = [
            'form' => [
                'environment' => $this->request->post('environment'),
                'search' => $this->request->post('search'),
                'reference' => $this->request->post('reference'),
                'gitref' => $this->request->post('gitref')
            ],

            'application' => $this->application,
            'branches' => $this->getBranches($this->application),
            'tags' => $this->getTags($this->application),
            'open' => $this->getPullRequests($this->application),
            'closed' => $this->getPullRequests($this->application, false),
            'environments' => $this->getBuildableEnvironments($this->application)
        ];

        $this->template->render($context);
    }

    /**
     * @param Application $application
     *
     * @return Environment[]
     */
    private function getBuildableEnvironments(Application $application)
    {
        $envs = $this->envRepo->getBuildableEnvironmentsByApplication($application);

        // if empty, throw them a bone with "test"
        if (!$envs) {
            $envs = $this->envRepo->findBy(['name' => 'test']);
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

        usort($tags, $this->releaseSorter());

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

        $user = strtolower($this->currentUser->handle());

        // sort by decreasing pull request number
        usort($pr, function ($a, $b) use ($user) {
            $prA = (int) $a['number'];
            $prB = (int) $b['number'];
            $loginA = isset($a['head']['user']['login']) ? strtolower($a['head']['user']['login']) : 'unknown';
            $loginB = isset($b['head']['user']['login']) ? strtolower($b['head']['user']['login']) : 'unknown';

            if ($loginA === $loginB && $loginA === $user) {
                // Everyone is owner
                return ($prA > $prB) ? -1 : 1;

            } elseif ($loginA === $user || $loginB === $user) {

                // One is owner
                if ($loginA === $user) return -1;
                if ($loginB === $user) return 1;
            }

            // No one is owner
            return ($prA > $prB) ? -1 : 1;
        });

        return $pr;
    }
}
