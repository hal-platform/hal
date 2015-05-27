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
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Repository\PushRepository;
use QL\HttpProblem\HttpProblemException;
use QL\Panthor\ControllerInterface;

class PushesController implements ControllerInterface
{
    use HypermediaResourceTrait;

    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type EntityRepository
     */
    private $repositoryRepo;
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
        $this->repositoryRepo = $em->getRepository(Repository::CLASS);
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
        $repository = $this->repositoryRepo->find($this->parameters['id']);

        if (!$repository instanceof Repository) {
            throw HttpProblemException::build(404, 'invalid-repository');
        }

        // get most recent 500 pushes, this is a hard limit.
        // @todo add paging to api
        $paginator = $this->pushRepo->getByRepository($repository, 500);

        // turn paginator into array
        $pushes = [];
        foreach ($paginator as $push) {
            $pushes[] = $push;
        }

        $status = (count($pushes) > 0) ? 200 : 404;

        $pushes = array_map(function ($push) {
            return $this->normalizer->link($push);
        }, $pushes);

        $this->formatter->respond($this->buildResource(
            [
                'count' => count($pushes)
            ],
            [],
            [
                'pushes' => $pushes
            ]
        ), $status);
    }
}
