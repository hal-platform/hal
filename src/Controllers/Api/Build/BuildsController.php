<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Api\Build;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Api\Hyperlink;
use Hal\UI\Api\Normalizer\BuildNormalizer;
use Hal\UI\Api\ResponseFormatter;
use Hal\UI\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Build;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Exception\HTTPProblemException;

class BuildsController implements ControllerInterface
{
    use HypermediaResourceTrait;

    const MAX_PER_PAGE = 25;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var EntityRepository
     */
    private $applicationRepo;
    private $buildRepo;

    /**
     * @var BuildNormalizer
     */
    private $normalizer;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param BuildNormalizer $normalizer
     * @param array $parameters
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        BuildNormalizer $normalizer,
        array $parameters
    ) {
        $this->formatter = $formatter;
        $this->buildRepo = $em->getRepository(Build::CLASS);
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->normalizer = $normalizer;

        $this->parameters = $parameters;
    }

    /**
     * @inheritDoc
     * @throws HttpProblemException
     */
    public function __invoke()
    {
        $application = $this->getApplication();
        $page = $this->getCurrentPage();

        $pagination = $this->buildRepo->getByApplication($application, self::MAX_PER_PAGE, ($page - 1));
        $total = count($pagination);

        $builds = [];
        foreach ($pagination as $build) {
            $builds[] = $this->normalizer->link($build);
        }

        $links = $this->buildPaginationLinks($page, $total, $application);
        $links['builds'] = $builds;

        $resource = $this->buildResource(
            [
                'count' => count($builds),
                'total' => $total,
                'page' => $page
            ],
            [],
            $links
        );

        $status = (count($builds) > 0) ? 200 : 404;
        $this->formatter->respond($resource, $status);
    }

    /**
     * @throws HTTPProblemException
     *
     * @return Application
     */
    private function getApplication()
    {
        $application = $this->applicationRepo->find($this->parameters['id']);

        if (!$application instanceof Application) {
            throw new HTTPProblemException(404, 'Invalid application ID specified');
        }

        return $application;
    }

    /**
     * @throws HTTPProblemException
     *
     * @return int
     */
    private function getCurrentPage()
    {
        $page = (isset($this->parameters['page'])) ? intval($this->parameters['page']) : 1;

        // 404, invalid page
        if ($page < 1) {
            throw new HTTPProblemException(404, 'Invalid page specified');
        }

        return $page;
    }

    /**
     * @param int $current
     * @param int $last
     * @param Application $application
     *
     * @return array
     */
    private function buildPaginationLinks($current, $total, Application $application)
    {
        $links = [];

        $prev = $current - 1;
        $next = $current + 1;
        $last = ceil($total / self::MAX_PER_PAGE);

        if ($current > 1) {
            $links['prev'] = new Hyperlink(['api.builds.history', ['id' => $application->id(), 'page' => $prev]]);
        }

        if ($next <= $last) {
            $links['next'] = new Hyperlink(['api.builds.history', ['id' => $application->id(), 'page' => $next]]);
        }

        if ($last > 1 && $current > 1) {
            $links['first'] = new Hyperlink(['api.builds.history', ['id' => $application->id(), 'page' => '1']]);
        }

        if ($last > 1) {
            $links['last'] = new Hyperlink(['api.builds.history', ['id' => $application->id(), 'page' => $last]]);
        }

        return $links;
    }
}
