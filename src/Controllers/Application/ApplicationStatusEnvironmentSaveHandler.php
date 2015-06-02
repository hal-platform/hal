<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Service\StickyEnvironmentService;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;

class ApplicationStatusEnvironmentSaveHandler implements MiddlewareInterface
{
    /**
     * @type EntityRepository
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
     * @param EntityManagerInterface $em
     * @param Request $request
     * @param Url $url
     * @param StickyEnvironmentService $service
     * @param array $parameters
     */
    public function __construct(
        EntityManagerInterface $em,
        Request $request,
        Url $url,
        StickyEnvironmentService $service,
        array $parameters
    ) {
        $this->envRepo = $em->getRepository(Environment::CLASS);
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
        $applicationId = (array_key_exists('id', $this->parameters)) ? $this->parameters['id'] : null;

        // Fall through to controller if no environment in query string
        if (!$environmentId || !$applicationId) {
            return;
        }

        // environment is valid. save to cookie.
        if ($environment = $this->envRepo->find($environmentId)) {

            $this->service->save($applicationId, $environment->id());
        }

        $this->url->redirectFor('repository.status', ['id' => $applicationId]);
    }
}
