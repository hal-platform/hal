<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Server;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Api\Normalizer\ServerNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Panthor\ControllerInterface;

class ServersController implements ControllerInterface
{
    use HypermediaResourceTrait;

    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type EntityRepository
     */
    private $serverRepo;

    /**
     * @type ServerNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param ServerNormalizer $normalizer
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        ServerNormalizer $normalizer
    ) {
        $this->formatter = $formatter;
        $this->serverRepo = $em->getRepository(Server::CLASS);
        $this->normalizer = $normalizer;
    }

    /**
     * {@inheritdoc}
     * @throws HttpProblemException
     */
    public function __invoke()
    {
        $servers = $this->serverRepo->findBy([], ['id' => 'ASC']);
        $status = (count($servers) > 0) ? 200 : 404;

        $servers = array_map(function ($server) {
            return $this->normalizer->link($server);
        }, $servers);

        $this->formatter->respond($this->buildResource(
            [
                'count' => count($servers)
            ],
            [],
            [
                'servers' => $servers
            ]
        ), $status);
    }
}
