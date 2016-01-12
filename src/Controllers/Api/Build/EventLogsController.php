<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Api\Build;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Api\Normalizer\BuildNormalizer;
use QL\Hal\Api\Normalizer\EventLogNormalizer;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Api\Utility\HypermediaResourceTrait;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Service\EventLogService;
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
    private $buildRepo;

    /**
     * @var EventLogService
     */
    private $logService;

    /**
     * @var EventLogNormalizer
     */
    private $eventLogNormalizer;

    /**
     * @var BuildNormalizer
     */
    private $buildNormalizer;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param Request $request
     * @param ResponseFormatter $formatter
     * @param EntityManagerInterface $em
     * @param EventLogService $logService
     * @param EventLogNormalizer $eventLogNormalizer
     * @param BuildNormalizer $buildNormalizer
     * @param array $parameters
     */
    public function __construct(
        Request $request,
        ResponseFormatter $formatter,
        EntityManagerInterface $em,
        EventLogService $logService,
        EventLogNormalizer $eventLogNormalizer,
        BuildNormalizer $buildNormalizer,
        array $parameters
    ) {
        $this->request = $request;
        $this->formatter = $formatter;
        $this->buildRepo = $em->getRepository(Build::CLASS);
        $this->logService = $logService;

        $this->eventLogNormalizer = $eventLogNormalizer;
        $this->buildNormalizer = $buildNormalizer;

        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     * @throws HTTPProblemException
     */
    public function __invoke()
    {
        $build = $this->buildRepo->find($this->parameters['id']);

        if (!$build instanceof Build) {
            throw new HTTPProblemException(404, 'Invalid build ID specified');
        }

        $logs = $this->logService->getLogs($build);

        $data = [
            'count' => count($logs)
        ];

        $embedded = [];

        $links = [
            'build' => $this->buildNormalizer->link($build),
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
