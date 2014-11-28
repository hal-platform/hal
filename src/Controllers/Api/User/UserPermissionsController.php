<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\User;

use QL\Hal\Api\EnvironmentNormalizer;
use QL\Hal\Api\RepositoryNormalizer;
use QL\Hal\Api\UserNormalizer;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Repository\UserRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Helpers\ApiHelper;
use QL\Hal\Services\PermissionsService;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * API User Permissions Controller
 */
class UserPermissionsController
{
    /**
     * @type ApiHelper
     */
    private $api;

    /**
     * @type UserRepository
     */
    private $userRepo;

    private $environmentNormalizer;

    private $repositoryNormalizer;

    /**
     * @var PermissionsService
     */
    private $permissions;

    /**
     * @param ApiHelper $api
     * @param UserRepository $userRepo
     * @param EnvironmentNormalizer $environmentNormalizer
     * @param RepositoryNormalizer $repositoryNormalizer
     * @param PermissionsService $permissions
     */
    public function __construct(
        ApiHelper $api,
        UserRepository $userRepo,
        EnvironmentNormalizer $environmentNormalizer,
        RepositoryNormalizer $repositoryNormalizer,
        PermissionsService $permissions
    ) {
        $this->api = $api;
        $this->userRepo = $userRepo;
        $this->environmentNormalizer = $environmentNormalizer;
        $this->repositoryNormalizer = $repositoryNormalizer;
        $this->permissions = $permissions;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     */
    public function __invoke(Request $request, Response $response, array $params = [])
    {
        $user = $this->userRepo->findOneBy(['id' => $params['id']]);
        if (!$user instanceof User) {
            return $response->setStatus(404);
        }

        $content = [
            '_links' => $this->api->parseLinks([
                'self' => ['href' => ['api.user.permissions', ['id' => $user->getId()]]],
                'user' => ['href' => ['api.user', ['id' => $user->getId()]]],
                'index' => ['href' => 'api.index']
            ]),
            'permissions' => [
                'debug' => [
                    'admin' => $this->permissions->allowAdmin($user)
                ],
                'push' => $this->formatUserPermissions($user, [$this->permissions, 'userPushPermissionPairs']),
                'build' => $this->formatUserPermissions($user, [$this->permissions, 'userBuildPermissionPairs'])
            ]
        ];

        $this->api->prepareResponse($response, $content);
    }

    /**
     * Prepare and format a collection of permission pairs as returned by source for a given user
     *
     * @param User $user
     * @param callable $source
     * @return array
     */
    protected function formatUserPermissions(User $user, callable $source)
    {
        $permissions = [];

        foreach (call_user_func($source, $user) as $env => $pairs) {

            if (isset($pairs[0]['environment']) && $pairs[0]['environment'] instanceof Environment) {
                $environment = $pairs[0]['environment'];
                $perEnvironment = [
                    'environment' => $this->api->parseLink([
                            'href' => ['api.environment', ['id' => $environment->getId()]],
                            'name' => $environment->getKey()
                        ]),
                    'repositories' => []
                ];

                foreach ($pairs as $pair) {
                    $repository = $pair['repository'];
                    $perEnvironment['repositories'][] = $this->api->parseLink([
                        'href' => ['api.repository', ['id' => $repository->getId()]],
                        'name' => $repository->getKey()
                    ]);
                }

                $permissions[$env] = $perEnvironment;
            }
        }

        return $permissions;
    }
}
