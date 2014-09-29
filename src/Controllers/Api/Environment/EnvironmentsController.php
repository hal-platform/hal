<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */
namespace QL\Hal\Controllers\Api\Environment;

use QL\Hal\Api\EnvironmentNormalizer;
use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Helpers\ApiHelper;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API Environments Controller
 */
class EnvironmentsController
{
    /**
     * @type ApiHelper
     */
    private $api;

    /**
     * @type EnvironmentRepository
     */
    private $envRepo;

    /**
     * @type EnvironmentNormalizer
     */
    private $normalizer;

    /**
     * @param ApiHelper $api
     * @param EnvironmentRepository $envRepo
     * @param EnvironmentNormalizer $normalizer
     */
    public function __construct(
        ApiHelper $api,
        EnvironmentRepository $envRepo,
        EnvironmentNormalizer $normalizer
    ) {
        $this->api = $api;
        $this->envRepo = $envRepo;
        $this->normalizer = $normalizer;
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __invoke(Request $request, Response $response)
    {
        $environments = $this->envRepo->findBy([], ['id' => 'ASC']);
        if (!$environments) {
            return $response->setStatus(404);
        }

        // using this to play with the idea of linked vs embedded resources
        $isResolved = false;

        $content = [
            'count' => count($environments),
            '_links' => [
                'self' => $this->api->parseLink(['href' => 'api.environments'])
            ]
        ];

        $content = array_merge_recursive($content, $this->normalizeEnvironments($environments, $isResolved));

        $this->api->prepareResponse($response, $content);
    }

    /**
     * @param array $environments
     * @param boolean $isResolved
     * @return array
     */
    private function normalizeEnvironments(array $environments, $isResolved)
    {
        $normalized = array_map(function($environment) use ($isResolved) {
            if ($isResolved) {
                return $this->normalizer->normalize($environment);
            }

            return $this->normalizer->linked($environment);
        }, $environments);


        $type = ($isResolved) ? '_embedded' : '_links';
        return [
            $type => [
                'environments' => $normalized
            ]
        ];
    }
}
