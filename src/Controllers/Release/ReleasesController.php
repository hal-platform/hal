<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Release;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\JobType\Release;
use Hal\Core\Repository\EnvironmentRepository;
use Hal\Core\Repository\JobType\ReleaseRepository;
use Hal\UI\Controllers\PaginationTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\SharedStaticConfiguration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ReleasesController implements ControllerInterface
{
    use PaginationTrait;
    use TemplatedControllerTrait;

    private const REGEX_ENV = '/(?:environment|env|e):([a-zA-Z-]+)/';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var ReleaseRepository
     */
    private $releaseRepo;

    /**
     * @var EnvironmentRepository
     */
    private $environmentRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->releaseRepo = $em->getRepository(Release::class);
        $this->environmentRepo = $em->getRepository(Environment::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $searchFilter = $request->getQueryParams()['search'] ?? '';

        $page = $this->getCurrentPage($request);

        $releases = $this->getReleases($application, ($page - 1), $searchFilter);
        $last = $this->getLastPage($releases, SharedStaticConfiguration::LARGE_PAGE_SIZE);

        return $this->withTemplate($request, $response, $this->template, [
            'page' => $page,
            'last' => $last,

            'application' => $application,
            'releases' => $releases,
            'search_filter' => $searchFilter,
        ]);
    }

    /**
     * @param Application $application
     * @param int $pageOffset
     * @param string $searchFilter
     *
     * @return Paginator
     */
    private function getReleases(Application $application, $pageOffset, $searchFilter)
    {
        if (!$environment = $this->getEnvironmentFromSearchFilter($searchFilter)) {
            return $this->releaseRepo->getByApplication(
                $application,
                SharedStaticConfiguration::LARGE_PAGE_SIZE,
                $pageOffset,
                $searchFilter
            );
        }

        $sanitizedSearchFilter = trim(preg_replace(self::REGEX_ENV, '', $searchFilter, 1));

        return $this->releaseRepo->getByApplicationForEnvironment(
            $application,
            $environment,
            SharedStaticConfiguration::LARGE_PAGE_SIZE,
            $pageOffset,
            $sanitizedSearchFilter
        );
    }

    /**
     * @param string $search
     *
     * @return Environment|null
     */
    private function getEnvironmentFromSearchFilter($search)
    {
        if (preg_match(self::REGEX_ENV, $search, $matches) === 1) {
            $name = strtolower(array_pop($matches));

            $env = $this->environmentRepo->findOneBy(['name' => $name]);
            if ($env instanceof Environment) {
                return $env;
            }
        }

        return null;
    }
}
