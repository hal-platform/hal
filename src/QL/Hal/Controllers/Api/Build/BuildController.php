<?php

namespace QL\Hal\Controllers\Api\Build;

use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Helpers\TimeHelper;
use QL\Hal\Helpers\UrlHelper;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\Consumer;

/**
 * API Build Controller
 */
class BuildController
{
    /**
     * @var ApiHelper
     */
    private $api;

    /**
     * @var TimeHelper
     */
    private $time;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @var BuildRepository
     */
    private $builds;

    /**
     * @param ApiHelper $api
     * @param TimeHelper $time
     * @param UrlHelper $url
     * @param BuildRepository $builds
     */
    public function __construct(
        ApiHelper $api,
        TimeHelper $time,
        UrlHelper $url,
        BuildRepository $builds
    ) {
        $this->api = $api;
        $this->time = $time;
        $this->url = $url;
        $this->builds = $builds;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = [], callable $notFound = null)
    {
        $build = $this->builds->findOneBy(['id' => $params['id']]);

        if (!($build instanceof Build)) {
            call_user_func($notFound);
            return;
        }

        $links = [
            'self' => ['href' => ['api.build', ['id' => $build->getId()]], 'type' => 'Build'],
            'index' => ['href' => 'api.index']
        ];

        $content = [
            'id' => $build->getId(),
            'url' => $this->url->urlFor('build', ['build' => $build->getId()]),
            'status' => $build->getStatus(),
            'start' => [
                'text' => $this->time->relative($build->getStart(), false),
                'datetime' => $this->time->format($build->getStart(), false, 'c')
            ],
            'end' => [
                'text' => $this->time->relative($build->getEnd(), false),
                'datetime' => $this->time->format($build->getEnd(), false, 'c')
            ],
            'reference' => [
                'text' => $build->getBranch(),
                'url' => $this->url->githubReferenceUrl(
                    $build->getRepository()->getGithubUser(),
                    $build->getRepository()->getGithubRepo(),
                    $build->getBranch()
                )
            ],
            'commit' => [
                'text' => $build->getCommit(),
                'url' => $this->url->githubCommitUrl(
                    $build->getRepository()->getGithubUser(),
                    $build->getRepository()->getGithubRepo(),
                    $build->getCommit()
                )
            ],
            'environment' => [
                'id' => $build->getEnvironment()->getId(),
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.environment', ['id' => $build->getEnvironment()->getId()]], 'type' => 'Environment']
                ])
            ],
            'repository' => [
                'id' => $build->getRepository()->getId(),
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.repository', ['id' => $build->getRepository()->getId()]]]
                ])
            ],
            'initiator' => []
        ];

        if ($build->getUser() instanceof User) {
            $content['initiator']['user'] = [
                'id' => $build->getUser()->getId(),
                '_links' => $this->api->parseLinks([
                    'self' => ['href' => ['api.user', ['id' => $build->getUser()->getId()]], 'type' => 'User']
                ])
            ];
        } else {
            $content['initiator']['user'] = null;
        }
        if ($build->getConsumer() instanceof Consumer) {
            $content['initiator']['consumer'] = [
                'id' => $build->getConsumer()->getId()
            ];
        } else {
            $content['initiator']['consumer'] = null;
        }

        $this->api->prepareResponse($response, $links, $content);
    }
}
