<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Api\Push;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Api\Normalizer\EventLogNormalizer;
use Hal\UI\Api\Normalizer\PushNormalizer;
use Hal\UI\Api\ResponseFormatter;
use Hal\UI\Api\Utility\HypermediaResourceTrait;
use Hal\UI\Service\EventLogService;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Repository\PushRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Exception\HTTPProblemException;
use Slim\Http\Request;

class EventLogsController implements ControllerInterface
{
    use HypermediaResourceTrait;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var EntityRepository
     */
    private $pushRepo;

    /**
     * @var EventLogService
     */
    private $logService;

    /**
     * @var EventLogNormalizer
     */
    private $eventLogNormalizer;

    /**
     * @var PushNormalizer
     */
    private $pushNormalizer;

    /**
     * @var array
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
