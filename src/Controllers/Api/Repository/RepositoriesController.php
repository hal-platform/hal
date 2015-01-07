<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Repository;

use QL\Hal\Api\Normalizer\RepositoryNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Panthor\ControllerInterface;

class RepositoriesController implements ControllerInterface
{
    use HypermediaResourceTrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @type RepositoryRepository
     */
    private $repositoryRepo;

    /**
     * @var RepositoryNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param RepositoryRepository $repositoryRepo
     * @param RepositoryNormalizer $normalizer
     */
    public function __construct(
        ResponseFormatter $formatter,
        RepositoryRepository $repositoryRepo,
        RepositoryNormalizer $normalizer
    ) {
        $this->formatter = $formatter;
        $this->repositoryRepo = $repositoryRepo;
        $this->normalizer = $normalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $repos = $this->repositoryRepo->findBy([], ['id' => 'ASC']);
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
                'repositories' => $repos
            ]
        ), $status);
    }
}
