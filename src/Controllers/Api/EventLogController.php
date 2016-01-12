<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Api;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Api\Normalizer\EventLogNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Core\Entity\EventLog;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Exception\HTTPProblemException;

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
     * @throws HTTPProblemException
     */
    public function __invoke()
    {
        $log = $this->logRepo->find($this->parameters['id']);

        if (!$log instanceof EventLog) {
            throw new HTTPProblemException(404, 'Invalid log ID specified');
        }

        $log = $this->normalizer->resource($log, ['data']);

        $this->formatter->respond($log);
    }
}
