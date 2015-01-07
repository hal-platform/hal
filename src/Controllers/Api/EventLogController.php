<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api;

use QL\Hal\Api\Normalizer\EventLogNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\EventLog;
use QL\Hal\Core\Entity\Repository\EventLogRepository;
use QL\HttpProblem\HttpProblemException;
use QL\Panthor\ControllerInterface;

class EventLogController implements ControllerInterface
{
    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type EventLogRepository
     */
    private $repository;

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
     * @param EventLogRepository $repository
     * @param EventLogNormalizer $normalizer
     * @param array $parameters
     */
    public function __construct(
        ResponseFormatter $formatter,
        EventLogRepository $repository,
        EventLogNormalizer $normalizer,
        array $parameters
    ) {
        $this->formatter = $formatter;
        $this->repository = $repository;
        $this->normalizer = $normalizer;

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HttpProblemException
     */
    public function __invoke()
    {
        $log = $this->repository->find($this->parameters['id']);

        if (!$log instanceof EventLog) {
            throw HttpProblemException::build(404, 'invalid-log');
        }

        $this->formatter->respond($log);
    }
}
