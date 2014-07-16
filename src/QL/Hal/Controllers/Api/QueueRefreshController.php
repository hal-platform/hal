<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api;

use Doctrine\Common\Collections\Criteria;
use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Api\BuildNormalizer;
use QL\Hal\Api\PushNormalizer;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\Entity\Repository\PushRepository;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Get the current status of one or more jobs.
 */
class QueueRefreshController
{
    /**
     * @var ApiHelper
     */
    private $api;

    /**
     * @var BuildNormalizer
     */
    private $buildNormalizer;

    /**
     * @var PushNormalizer
     */
    private $pushNormalizer;

    /**
     * @var BuildRepository
     */
    private $buildRepo;

    /**
     * @var PushRepository
     */
    private $pushRepo;

    /**
     * @param ApiHelper $api
     * @param BuildNormalizer $buildNormalizer
     * @param PushNormalizer $pushNormalizer
     * @param BuildRepository $buildRepo
     * @param PushRepository $pushRepo
     */
    public function __construct(
        ApiHelper $api,
        BuildNormalizer $buildNormalizer,
        PushNormalizer $pushNormalizer,
        BuildRepository $buildRepo,
        PushRepository $pushRepo
    ) {
        $this->api = $api;
        $this->buildNormalizer = $buildNormalizer;
        $this->pushNormalizer = $pushNormalizer;
        $this->buildRepo = $buildRepo;
        $this->pushRepo = $pushRepo;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @return null
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        if (!isset($params['uniqueId'])) {
            // Need some kind of error messaging
            $response->setBody('This should never have happened.');
            return $response->setStatus(400);
        }

        $identifiers = explode(' ', $params['uniqueId']);

        if (!$jobs = $this->retrieveJobs($identifiers)) {
            return $response->setStatus(404);
        }

        $jobs = $this->formatQueue($jobs);

        $this->api->prepareResponse(
            $response,
            [
                'self' => ['href' => ['api.queue.refresh', ['uniqueId' => implode('+', $identifiers)]]],
            ],
            $jobs
        );
    }

    /**
     * @param array $identifiers
     * @param string $prefix
     * @return array
     */
    private function filterIdentifiers(array $identifiers, $prefix)
    {
        $prefixLength = strlen($prefix);

        $filtered = array_filter($identifiers, function($v) use ($prefix, $prefixLength) {
            return (substr($v, 0, $prefixLength) === $prefix);
        });

        array_walk($filtered, function(&$v) use ($prefixLength) {
            $v = substr($v, $prefixLength);
        });

        return $filtered;
    }

    /**
     * @param Build[]|Push[] $queue
     * @return array
     */
    private function formatQueue(array $queue)
    {
        $normalizedQueue = [];

        foreach ($queue as $job) {

            if ($job instanceof Push) {
                $normalized = $this->pushNormalizer->normalize($job);
                $normalized = array_merge([
                    'uniqueId' => 'push-' . $normalized['id'],
                    'type' => 'push'
                ], $normalized);

            } else {
                $normalized = $this->buildNormalizer->normalize($job);
                $normalized = array_merge([
                    'uniqueId' => 'build-' . $normalized['id'],
                    'type' => 'build'
                ], $normalized);
            }

            $normalizedQueue[] = $normalized;
        }

        return $normalizedQueue;
    }

    /**
     * @param array $identifiers
     * @return array
     */
    private function retrieveJobs($identifiers)
    {
        $builds = $pushes = [];

        if ($buildIds = $this->filterIdentifiers($identifiers, 'build-')) {
            $buildCriteria = (new Criteria)
                ->where(Criteria::expr()->in('id', $buildIds));
            $builds = $this->buildRepo->matching($buildCriteria);
            $builds = $builds->toArray();
        }

        if ($pushIds = $this->filterIdentifiers($identifiers, 'push-')) {
            $pushCriteria = (new Criteria)
                ->where(Criteria::expr()->in('id', $pushIds));
            $pushes = $this->pushRepo->matching($pushCriteria);
            $pushes = $pushes->toArray();
        }

        return array_merge($builds, $pushes);
    }
}
