<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Api\Normalizer\EventLogNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\EventLog;
use QL\HttpProblem\HttpProblemException;
use QL\Panthor\ControllerInterface;

class EventLogController implements ControllerInterface
{
    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type EntityRepository
     */
    private $logRepo;

    /**
     * @type EventLogNormalizer
     */
    private $normalizer;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param EventLogNormalizer $normalizer
     * @param array $parameters
     */
    public function __construct(
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        EventLogNormalizer $normalizer,
        array $parameters
    ) {
        $this->formatter = $formatter;
        $this->logRepo = $em->getRepository(EventLog::CLASS);
        $this->normalizer = $normalizer;

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HttpProblemException
     */
    public function __invoke()
    {
        $log = $this->logRepo->find($this->parameters['id']);

        if (!$log instanceof EventLog) {
            throw HttpProblemException::build(404, 'invalid-log');
        }

        $log = $this->normalizer->resource($log, ['data']);

        $this->formatter->respond($log);
    }
}
