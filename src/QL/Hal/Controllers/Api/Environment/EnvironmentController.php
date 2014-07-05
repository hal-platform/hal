<?php

namespace QL\Hal\Controllers\Api\Environment;

use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Helpers\UrlHelper;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;

/**
 * API Environment Controller
 */
class EnvironmentController
{
    /**
     * @var ApiHelper
     */
    private $api;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @var EnvironmentRepository
     */
    private $environments;

    /**
     * @param ApiHelper $api
     * @param UrlHelper $url
     * @param EnvironmentRepository $environments
     */
    public function __construct(
        ApiHelper $api,
        UrlHelper $url,
        EnvironmentRepository $environments
    ) {
        $this->api = $api;
        $this->url = $url;
        $this->environments = $environments;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $environment = $this->environments->findOneBy(['id' => $params['id']]);

        if (!($environment instanceof Environment)) {
            call_user_func($notFound);
            return;
        }

        $links = [
            'self' => ['href' => ['api.environment', ['id' => $environment->getId()]], 'type' => 'Environment'],
            'environments' => ['href' => 'api.environments', 'type' => 'Environments'],
            'index' => ['href' => 'api.index']
        ];

        $content = [
            'id' => $environment->getId(),
            'url' => $this->url->urlFor('environment', ['id' => $environment->getId()]),
            'key' => $environment->getKey(),
            'order' => $environment->getOrder()
        ];

        $this->api->prepareResponse($response, $links, $content);
    }
}
