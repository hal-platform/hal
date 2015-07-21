<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Push;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Api\Normalizer\PushNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Repository\PushRepository;
use QL\HttpProblem\HttpProblemException;
use QL\Panthor\ControllerInterface;

class PushesController implements ControllerInterface
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
    private $pushRepo;

    /**
     * @type PushNormalizer
     */
    private $normalizer;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param PushNormalizer $normalizer
     * @param array $parameters
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        PushNormalizer $normalizer,
        array $parameters
    ) {
        $this->formatter = $formatter;
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->pushRepo = $em->getRepository(Push::CLASS);
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

        // get most recent 500 pushes, this is a hard limit.
        // @todo add paging to api
        $pagination = $this->pushRepo->getByApplication($application, self::MAX_PER_PAGE, ($page - 1));
        $total = count($pagination);

        $pushes = [];
        foreach ($pagination as $push) {
            $pushes[] = $this->normalizer->link($push);
        }

        $links = $this->buildPaginationLinks($page, $total, $application);
        $links['pushes'] = $push;

        $resource = $this->buildResource(
            [
                'count' => count($pushes),
                'total' => $total,
                'page' => $page
            ],
            [],
            $links
        );

        $status = (count($pushes) > 0) ? 200 : 404;
        $this->formatter->respond($resource, $status);
    }

    /**
     * @throws HttpProblemException
     *
     * @return Application
     */
    private function getApplication()
    {
        $application = $this->applicationRepo->find($this->parameters['id']);

        if (!$application instanceof Application) {
            throw HttpProblemException::build(404, 'invalid-application');
        }

        return $application;
    }

    /**
     * @throws HttpProblemException
     *
     * @return int
     */
    private function getCurrentPage()
    {
        $page = (isset($this->parameters['page'])) ? intval($this->parameters['page']) : 1;

        // 404, invalid page
        if ($page < 1) {
            throw HttpProblemException::build(404, 'invalid-page');
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
            $links['prev'] = ['href' => ['api.pushes.history', ['id' => $application->id(), 'page' => $prev]]];
        }

        if ($next <= $last) {
            $links['next'] = ['href' => ['api.pushes.history', ['id' => $application->id(), 'page' => $next]]];
        }

        if ($last > 1 && $current > 1) {
            $links['first'] = ['href' => ['api.pushes.history', ['id' => $application->id(), 'page' => '1']]];
        }

        if ($last > 1) {
            $links['last'] = ['href' => ['api.pushes.history', ['id' => $application->id(), 'page' => $last]]];
        }

        return $links;
    }
}
