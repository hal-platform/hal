<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Build;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Api\Normalizer\BuildNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Build;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Exception\HTTPProblemException;

class BuildsController implements ControllerInterface
{
    use HypermediaResourceTrait;

    const MAX_PER_PAGE = 25;

    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type EntityRepository
     */
    private $applicationRepo;
    private $buildRepo;

    /**
     * @type BuildNormalizer
     */
    private $normalizer;

    /**
     * @type array
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
     * {@inheritdoc}
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
            $links['prev'] = ['href' => ['api.builds.history', ['id' => $application->id(), 'page' => $prev]]];
        }

        if ($next <= $last) {
            $links['next'] = ['href' => ['api.builds.history', ['id' => $application->id(), 'page' => $next]]];
        }

        if ($last > 1 && $current > 1) {
            $links['first'] = ['href' => ['api.builds.history', ['id' => $application->id(), 'page' => '1']]];
        }

        if ($last > 1) {
            $links['last'] = ['href' => ['api.builds.history', ['id' => $application->id(), 'page' => $last]]];
        }

        return $links;
    }
}
