<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Build;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\PaginationTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Repository\BuildRepository;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class BuildsController implements ControllerInterface
{
    use PaginationTrait;
    use TemplatedControllerTrait;

    private const MAX_PER_PAGE = 25;

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
     * @var callable
     */
    private $notFound;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param callable $notFound
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em, callable $notFound)
    {
        $this->template = $template;
        $this->buildRepo = $em->getRepository(Build::class);
        $this->environmentRepo = $em->getRepository(Environment::class);

        $this->notFound = $notFound;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $searchFilter = $request->getQueryParams()['search'] ?? '';

        $params = $request
            ->getAttribute('route')
            ->getArguments();

        $page = $this->getCurrentPage($params);
        if ($page === null) {
            return ($this->notFound)($request, $response);
        }

        if ($environment = $this->getEnvironmentFromSearchFilter($searchFilter)) {
            $sanitizedSearchFilter = trim(preg_replace(self::REGEX_ENV, '', $searchFilter, 1));

            $builds = $this->buildRepo->getByApplicationForEnvironment($application, $environment, self::MAX_PER_PAGE, ($page-1), $sanitizedSearchFilter);
        } else {
            $builds = $this->buildRepo->getByApplication($application, self::MAX_PER_PAGE, ($page-1), $searchFilter);
        }

        $total = count($builds);
        $last = ceil($total / self::MAX_PER_PAGE);

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
