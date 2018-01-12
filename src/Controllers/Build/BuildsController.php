<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Build;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\JobType\Build;
use Hal\Core\Entity\Environment;
use Hal\Core\Repository\EnvironmentRepository;
use Hal\Core\Repository\JobType\BuildRepository;
use Hal\UI\Controllers\PaginationTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\SharedStaticConfiguration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class BuildsController implements ControllerInterface
{
    use PaginationTrait;
    use TemplatedControllerTrait;

    private const REGEX_ENV = '/(environment|env|e):([a-zA-Z-]+)/';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var BuildRepository
     */
    private $buildRepo;

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
        $this->buildRepo = $em->getRepository(Build::class);
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

        if ($environment = $this->getEnvironmentFromSearchFilter($searchFilter)) {
            $sanitizedSearchFilter = trim(preg_replace(self::REGEX_ENV, '', $searchFilter, 1));

            $builds = $this->buildRepo->getByApplicationForEnvironment($application, $environment, SharedStaticConfiguration::LARGE_PAGE_SIZE, ($page - 1), $sanitizedSearchFilter);
        } else {
            $builds = $this->buildRepo->getByApplication($application, SharedStaticConfiguration::LARGE_PAGE_SIZE, ($page - 1), $searchFilter);
        }

        $last = $this->getLastPage($builds, SharedStaticConfiguration::LARGE_PAGE_SIZE);

        return $this->withTemplate($request, $response, $this->template, [
            'page' => $page,
            'last' => $last,

            'application' => $application,
            'builds' => $builds,
            'search_filter' => $searchFilter
        ]);
    }

    /**
     * @param string $search
     *
     * @return Environment|null|false
     */
    private function getEnvironmentFromSearchFilter($search)
    {
        if (preg_match(self::REGEX_ENV, $search, $matches) === 1) {
            $name = strtolower(array_pop($matches));

            return $this->environmentRepo->findOneBy(['name' => $name]);
        }

        return false;
    }
}
