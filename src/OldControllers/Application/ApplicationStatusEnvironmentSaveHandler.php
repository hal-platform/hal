<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Service\StickyEnvironmentService;
use QL\Hal\Core\Entity\Environment;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;

class ApplicationStatusEnvironmentSaveHandler implements MiddlewareInterface
{
    /**
     * @var EntityRepository
     */
    private $envRepo;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var StickyEnvironmentService
     */
    private $service;

    /**
     * @var Url
     */
    private $url;

    /**
     * @var array
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
     * @inheritDoc
     */
    public function __invoke()
    {
        $environmentId = $this->request->get('environment');
        $applicationId = (array_key_exists('application', $this->parameters)) ? $this->parameters['application'] : null;

        // Fall through to controller if no environment in query string
        if (!$environmentId || !$applicationId) {
            return;
        }

        // environment is valid. save to cookie.
        if ($environment = $this->envRepo->find($environmentId)) {
            $this->service->save($applicationId, $environment->id());
        }

        $this->url->redirectFor('application.status', ['application' => $applicationId]);
    }
}