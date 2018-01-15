<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Organization;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Organization;
use Hal\Core\Entity\User\UserPermission;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Security\AuthorizationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveOrganizationHandler implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;

    const MSG_SUCCESS = '"%s" organization removed.';
    const ERR_HAS_APPLICATIONS = 'Cannot remove organization. All associated applications must first be transferred.';

    /**
     * @var EntityRepository
     */
    private $applicationRepo;
    private $permissionRepo;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var AuthorizationService
     */
    private $authorizationService;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param EntityManagerInterface $em
     * @param AuthorizationService $authorizationService
     * @param URI $uri
     */
    public function __construct(EntityManagerInterface $em, AuthorizationService $authorizationService, URI $uri)
    {
        $this->em = $em;
        $this->applicationRepo = $em->getRepository(Application::class);
        $this->permissionRepo = $em->getRepository(UserPermission::class);

        $this->authorizationService = $authorizationService;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $organization = $request->getAttribute(Organization::class);

        if (!$this->isCSRFValid($request)) {
            $this->withFlashError($request, $this->CSRFError());
            return $this->withRedirectRoute($response, $this->uri, 'organization', ['organization' => $organization->id()]);
        }

        if ($this->applicationRepo->findOneBy(['organization' => $organization])) {
            $this->withFlashError($request, self::ERR_HAS_APPLICATIONS);
            return $this->withRedirectRoute($response, $this->uri, 'organization', ['organization' => $organization->id()]);
        }

        // Remove targets and permissions first
        $this->removePermissions($organization);

        $this->em->remove($organization);
        $this->em->flush();

        $this->withFlashSuccess($request, sprintf(self::MSG_SUCCESS, $organization->name()));
        return $this->withRedirectRoute($response, $this->uri, 'applications');
    }

    /**
     * @param Organization $organization
     *
     * @return void
     */
    private function removePermissions(Organization $organization)
    {
        $permission = $this->permissionRepo->findBy(['organization' => $organization]);
        foreach ($permission as $permission) {
            $this->authorizationService->removeUserPermissions($permission, true);
        }
    }
}
