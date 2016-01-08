<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Push;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Api\Normalizer\EventLogNormalizer;
use QL\Hal\Api\Normalizer\PushNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Repository\PushRepository;
use QL\Hal\Service\EventLogService;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Exception\HTTPProblemException;
use Slim\Http\Request;

class EventLogsController implements ControllerInterface
{
    use HypermediaResourceTrait;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type EntityRepository
     */
    private $pushRepo;

    /**
     * @type EventLogService
     */
    private $logService;

    /**
     * @type EventLogNormalizer
     */
    private $eventLogNormalizer;

    /**
     * @type PushNormalizer
     */
    private $pushNormalizer;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param Request $request
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param EventLogNormalizer $eventLogNormalizer
     * @param PushNormalizer $pushNormalizer
     * @param EventLogService $logService
     * @param array $parameters
     */
    public function __construct(
        Request $request,
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        EventLogService $logService,
        EventLogNormalizer $eventLogNormalizer,
        PushNormalizer $pushNormalizer,
        array $parameters
    ) {
        $this->request = $request;
        $this->formatter = $formatter;
        $this->pushRepo = $em->getRepository(Push::CLASS);
        $this->logService = $logService;

        $this->eventLogNormalizer = $eventLogNormalizer;
        $this->pushNormalizer = $pushNormalizer;

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HTTPProblemException
     */
    public function __invoke()
    {
        $push = $this->pushRepo->find($this->parameters['id']);

        if (!$push instanceof Push) {
            throw new HTTPProblemException(404, 'Invalid push ID specified');
        }

        $logs = $this->logService->getLogs($push);

        $data = [
            'count' => count($logs)
        ];

        $embedded = [];

        $links = [
            'push' => $this->pushNormalizer->link($push),
        ];

        if ($this->isEmbedded()) {

            array_walk($logs, function(&$log) {
                $log = $this->eventLogNormalizer->resource($log);
            });

            $embedded['logs'] = $logs;

        } else {

            array_walk($logs, function(&$log) {
                $log = $this->eventLogNormalizer->link($log);
            });

            $links['logs'] = $logs;
        }

        $resource = $this->buildResource($data, $embedded, $links);

        $status = (count($logs) > 0) ? 200 : 404;
        $this->formatter->respond($resource, $status);
    }

    /**
     * @return bool
     */
    private function isEmbedded()
    {
        if (!$embed = $this->request->get('embed')) {
            return false;
        }

        $embed = explode(',', $embed);

        return in_array('logs', $embed);
    }
}
