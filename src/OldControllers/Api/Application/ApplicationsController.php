<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Api\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Api\Normalizer\ApplicationNormalizer;
use Hal\UI\Api\ResponseFormatter;
use Hal\UI\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;

class ApplicationsController implements ControllerInterface
{
    use HypermediaResourceTrait;
    use SortingTrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var EntityRepository
     */
    private $applicationRepo;

    /**
     * @var ApplicationNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param ApplicationNormalizer $normalizer
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        ApplicationNormalizer $normalizer
    ) {
        $this->formatter = $formatter;
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->normalizer = $normalizer;
    }

    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        $applications = $this->applicationRepo->findAll();
        usort($applications, $this->applicationSorter());

        $status = (count($applications) > 0) ? 200 : 404;

        array_walk($applications, function (&$app) {
            $app = $this->normalizer->link($app);
        });

        $resource = $this->buildResource(
            [
                'count' => count($applications)
            ],
            [],
            [
                'applications' => $applications
            ]
        );

        $this->formatter->respond($resource, $status);
    }
}
