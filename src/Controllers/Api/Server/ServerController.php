<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Server;

use Doctrine\ORM\EntityRepository;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\Server;
use QL\HttpProblem\HttpProblemException;
use QL\Panthor\ControllerInterface;

class ServerController implements ControllerInterface
{
    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type EntityRepository
     */
    private $serverRepo;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityRepository $serverRepo
     * @param array $parameters
     */
    public function __construct(ResponseFormatter $formatter, EntityRepository $serverRepo, array $parameters)
    {
        $this->formatter = $formatter;
        $this->serverRepo = $serverRepo;

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HttpProblemException
     */
    public function __invoke()
    {
        $server = $this->serverRepo->find($this->parameters['id']);

        if (!$server instanceof Server) {
            throw HttpProblemException::build(404, 'invalid-server');
        }

        $this->formatter->respond($server);
    }
}
