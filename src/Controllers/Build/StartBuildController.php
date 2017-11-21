<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Build;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Security\UserAuthorizations;
use Hal\UI\Service\GitHubService;
use Hal\UI\Service\StickyEnvironmentService;
use Hal\UI\Utility\ReleaseSortingTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\User;
use Hal\Core\Repository\TargetRepository;
use Hal\Core\Repository\EnvironmentRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class StartBuildController implements ControllerInterface
{
    use ReleaseSortingTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EnvironmentRepository
     */
    private $environmentRepository;

    /**
     * @var TargetRepository
     */
    private $targetRepository;

    /**
     * @var GitHubService
     */
    private $github;

    /**
     * @var StickyEnvironmentService
     */
    private $stickyService;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param GitHubService $github
     * @param StickyEnvironmentService $stickyService
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        GitHubService $github,
        StickyEnvironmentService $stickyService
    ) {
        $this->template = $template;
        $this->github = $github;
        $this->stickyService = $stickyService;

        $this->environmentRepository = $em->getRepository(Environment::class);
        $this->targetRepository = $em->getRepository(Target::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $user = $this->getUser($request);
        $userAuthorizations = $this->getAuthorizations($request);

        $prSorter = $this->sorterPullRequests($user);

        $openPR = $this->getPullRequests($application);
        $closedPR = $this->getPullRequests($application, false);
        usort($openPR, $prSorter);
        usort($closedPR, $prSorter);

        $form = $this->getFormData($request, $application);

        $targets = $this->getTargetStatusesForEnvironment($application, $userAuthorizations, $form['environment']);

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,

            'application' => $application,
            'branches' => $this->getBranches($application),
            'tags' => $this->getTags($application),
            'open' => $openPR,
            'closed' => $closedPR,

            'environments' => $this->getBuildableEnvironments($application),
        ] + $targets);
    }

    /**
     * @param ServerRequestInterface $request
     * @param Application $application
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request, Application $application)
    {
        // Automatically select an environment from sticky pref if this is fresh form
        if ($environment = $request->getAttribute(Environment::class)) {
            $env = $environment->id();
        } else {
            $env = $this->stickyService->get($request, $application);
        }

        return [
            'environment' => $env,
            'search' => $request->getParsedBody()['search'] ?? '',
            'reference' => $request->getParsedBody()['reference'] ?? '',
            'gitref' => $request->getParsedBody()['gitref'] ?? ''
        ];
    }

    /**
     * @param Application $application
     *
     * @return Environment[]
     */
    private function getBuildableEnvironments(Application $application)
    {
        $envs = $this->environmentRepository->getBuildableEnvironmentsByApplication($application);

        // if empty, throw them a bone with "test"
        if (!$envs) {
            $envs = $this->environmentRepository->findBy(['name' => 'test']);
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
        $branches = $this->github->branches(
            $application->gitHub()->owner(),
            $application->github()->repository()
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
     * Get an array of tags for an application
     *
     * @param Application $application
     *
     * @return array
     */
    private function getTags(Application $application)
    {
        $tags = $this->github->tags(
            $application->github()->owner(),
            $application->github()->repository()
        );

        usort($tags, $this->releaseSorter());

        return array_slice($tags, 0, 25);
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
        $owner = $application->gitHub()->owner();
        $repo = $application->gitHub()->repository();

        if ($open) {
            $pr = $this->github->openPullRequests($owner, $repo);
        } else {
            $pr = $this->github->closedPullRequests($owner, $repo);
        }

        return $pr;
    }

    /**
     * @param User $user
     *
     * @return callable
     */
    private function sorterPullRequests(User $user)
    {
        $username = $user->username();

        return function ($a, $b) use ($username) {
            $prA = (int) $a['number'];
            $prB = (int) $b['number'];
            $loginA = isset($a['head']['user']['login']) ? strtolower($a['head']['user']['login']) : 'unknown';
            $loginB = isset($b['head']['user']['login']) ? strtolower($b['head']['user']['login']) : 'unknown';

            if ($loginA === $loginB && $loginA === $username) {
                // Everyone is owner
                return ($prA > $prB) ? -1 : 1;

            } elseif ($loginA === $username || $loginB === $username) {

                // One is owner
                if ($loginA === $username) return -1;
                if ($loginB === $username) return 1;
            }

            // No one is owner
            return ($prA > $prB) ? -1 : 1;
        };
    }

    /**
     * @param Application $application
     * @param User $user
     * @param Environment|string $env
     *
     * @return array
     */
    public function getTargetStatusesForEnvironment(Application $application, UserAuthorizations $userAuthorizations, $env)
    {
        $environment = '';
        if ($env instanceof Environment) {
            $environment = $env;
        } elseif ($env === 'global') {
            $environment = null;
        } elseif ($env) {
            $environment = $this->environmentRepository->find($env);
        }

        if (!$environment) {
            return [
                'can_deploy' => false,
                'available_targets' => []
            ];
        }

        $available = $this->targetRepository->getByApplicationAndEnvironment($application, $environment);

        $canPush = $userAuthorizations->canDeploy($application, $environment);

        return [
            'can_deploy' => $canPush,
            'available_targets' => $available
        ];
    }
}
