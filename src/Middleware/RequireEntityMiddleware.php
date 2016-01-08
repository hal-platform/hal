<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Credential;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\DeploymentView;
use QL\Hal\Core\Entity\DeploymentPool;
use QL\Hal\Core\Entity\EncryptedProperty;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserPermission;
use QL\Hal\Core\Entity\UserType;
use QL\Panthor\Exception\HTTPProblemException;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\MiddlewareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Automatically look up entities in the route parameters and fetch them from the DB.
 *
 * Throw a 404 if not found in the DB.
 */
class RequireEntityMiddleware implements MiddlewareInterface
{
    const KEY_TEMPLATE = 'hal.%s';

    /**
     * @type ContainerInterface
     */
    private $di;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @type bool
     */
    private $isAPI;

    /**
     * @type array
     */
    private $map;

    /**
     * @param ContainerInterface $di
     * @param EntityManagerInterface $em
     * @param NotFound $notFound
     * @param array $parameters
     * @param bool $isAPI
     */
    public function __construct(
        ContainerInterface $di,
        EntityManagerInterface $em,
        NotFound $notFound,
        array $parameters,
        $isAPI = false
    ) {
        $this->di = $di;
        $this->em = $em;

        $this->notFound = $notFound;
        $this->parameters = $parameters;
        $this->isAPI = $isAPI;

        // whitelist of route parameters and the entity they map to.
        $this->map = [
            'build' => Build::CLASS,
            'push' => Push::CLASS,

            'user' => User::CLASS,
            'userPermission' => UserPermission::CLASS,
            'userType' => UserType::CLASS,

            'application' => Application::CLASS,
            'credential' => Credential::CLASS,
            'deployment' => Deployment::CLASS,
            'encrypted' => EncryptedProperty::CLASS,
            'environment' => Environment::CLASS,
            'server' => Server::CLASS,

            'pool' => DeploymentPool::CLASS,
            'view' => DeploymentView::CLASS,
        ];
    }

    /**
     * {@inheritdoc}
     * @throws HTTPProblemException
     */
    public function __invoke()
    {
        foreach ($this->parameters as $entity => $id) {

            if (!isset($this->map[$entity])) {
                continue;
            }

            if (!$this->lookup($entity, $id)) {

                if ($this->isAPI) {
                    throw new HTTPProblemException(404, sprintf('%s not found', ucfirst($entity)));
                } else {
                    return call_user_func($this->notFound);
                }
            }
        }
    }

    /**
     * @param string $entityName
     * @param string $id
     *
     * @return bool
     */
    private function lookup($entityName, $id)
    {
        $fq = $this->map[$entityName];
        $key = sprintf(self::KEY_TEMPLATE, $entityName);

        $repository = $this->em->getRepository($fq);
        if (!$entity = $repository->find($id)) {
            return false;
        }

        $this->di->set($key, $entity);
        return true;
    }
}
