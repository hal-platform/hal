<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Organization;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Organization;
use Hal\Core\Entity\User\UserPermission;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class OrganizationController implements ControllerInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $applicationRepo;
    private $permissionRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->applicationRepo = $em->getRepository(Application::class);
        $this->permissionRepo = $em->getRepository(UserPermission::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $organization = $request->getAttribute(Organization::class);

        $applications = $this->applicationRepo
            ->findBy(['organization' => $organization], ['name' => 'ASC']);

        return $this->withTemplate($request, $response, $this->template, [
            'organization' => $organization,

            'applications' => $applications,
            'permissions' => $this->getPermissions($organization),
        ]);
    }

    /**
     * @param Organization $organization
     *
     * @return array
     */
    private function getPermissions(Organization $organization)
    {
        return $this->permissionRepo->findBy(['organization' => $organization]);
    }
}
