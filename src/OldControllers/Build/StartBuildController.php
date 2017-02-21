<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Build;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Service\GitHubService;
use Hal\UI\Service\StickyEnvironmentService;
use Hal\UI\Utility\ReleaseSortingTrait;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class StartBuildController implements ControllerInterface
{
    use ReleaseSortingTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EnvironmentRepository
     */
    private $envRepo;

    /**
     * @var GitHubService
     */
    private $github;

    /**
     * @var StickyEnvironmentService
     */
    private $stickyService;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Application
     */
    private $application;

    /**
     * @var User
     */
    private $currentUser;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param GitHubService $github
     * @param StickyEnvironmentService $stickyService
     * @param Request $request
     * @param Application $application
     * @param User $currentUser
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        GitHubService $github,
        StickyEnvironmentService $stickyService,
        Request $request,
        Application $application,
        User $currentUser
    ) {
        $this->template = $template;
        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->github = $github;
        $this->stickyService = $stickyService;

        $this->request = $request;
        $this->application = $application;
        $this->currentUser = $currentUser;
    }

    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        // Automatically select an environment from sticky pref if this is fresh form
        $env = $this->request->post('environment');
        if ($env === null) {
            $env = $this->stickyService->get($this->application);
        }

        $context = [
            'form' => [
                'environment' => $env,
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
