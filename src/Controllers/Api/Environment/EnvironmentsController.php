<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */
namespace QL\Hal\Controllers\Api\Environment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Api\Normalizer\EnvironmentNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Environment;
use QL\Panthor\ControllerInterface;

class EnvironmentsController implements ControllerInterface
{
    use HypermediaResourceTrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var EntityRepository
     */
    private $envRepo;

    /**
     * @var EnvironmentNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param EnvironmentNormalizer $normalizer
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        EnvironmentNormalizer $normalizer
    ) {
        $this->formatter = $formatter;
        $this->envRepo = $em->getRepository(Environment::CLASS);
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
