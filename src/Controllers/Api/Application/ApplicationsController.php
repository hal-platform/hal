<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Api\Normalizer\ApplicationNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Application;
use QL\Panthor\ControllerInterface;

class ApplicationsController implements ControllerInterface
{
    use HypermediaResourceTrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @type EntityRepository
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
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $repos = $this->applicationRepo->findBy([], ['id' => 'ASC']);
        $status = (count($repos) > 0) ? 200 : 404;

        $repos = array_map(function ($repo) {
            return $this->normalizer->link($repo);
        }, $repos);

        $this->formatter->respond($this->buildResource(
            [
                'count' => count($repos)
            ],
            [],
            [
                'applications' => $repos
            ]
        ), $status);
    }
}
