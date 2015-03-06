<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */
namespace QL\Hal\Controllers\Api\Environment;

use QL\Hal\Api\Normalizer\EnvironmentNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Panthor\ControllerInterface;

class EnvironmentsController implements ControllerInterface
{
    use HypermediaResourceTrait;

    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type EnvironmentRepository
     */
    private $envRepo;

    /**
     * @type EnvironmentNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param EnvironmentRepository $envRepo
     * @param EnvironmentNormalizer $normalizer
     */
    public function __construct(
        ResponseFormatter $formatter,
        EnvironmentRepository $envRepo,
        EnvironmentNormalizer $normalizer
    ) {
        $this->formatter = $formatter;
        $this->envRepo = $envRepo;
        $this->normalizer = $normalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $environments = $this->envRepo->findBy([], ['id' => 'ASC']);
        $status = (count($environments) > 0) ? 200 : 404;

        $environments = array_map(function ($environment) {
            return $this->normalizer->link($environment);
        }, $environments);

        $this->formatter->respond($this->buildResource(
            [
                'count' => count($environments)
            ],
            [],
            [
                'environments' => $environments
            ]
        ), $status);
    }
}
