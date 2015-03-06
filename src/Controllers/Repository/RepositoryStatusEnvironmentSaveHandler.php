<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository;

use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Hal\Services\StickyEnvironmentService;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;

class RepositoryStatusEnvironmentSaveHandler implements MiddlewareInterface
{
    /**
     * @type EnvironmentRepository
     */
    private $envRepo;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type StickyEnvironmentService
     */
    private $service;

    /**
     * @type Url
     */
    private $url;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param EnvironmentRepository $envRepo
     * @param Request $request
     * @param Url $url
     * @param StickyEnvironmentService $service
     * @param array $parameters
     */
    public function __construct(
        EnvironmentRepository $envRepo,
        Request $request,
        Url $url,
        StickyEnvironmentService $service,
        array $parameters
    ) {
        $this->envRepo = $envRepo;
        $this->request = $request;
        $this->url = $url;
        $this->service = $service;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $environmentId = $this->request->get('environment');
        $repoId = (array_key_exists('id', $this->parameters)) ? $this->parameters['id'] : null;

        // Fall through to controller if no environment in query string
        if (!$environmentId || !$repoId) {
            return;
        }

        // environment is valid. save to cookie.
        if ($environment = $this->envRepo->find($environmentId)) {

            $this->service->save($repoId, $environment->getId());
        }

        $this->url->redirectFor('repository.status', ['id' => $repoId]);
    }

}
