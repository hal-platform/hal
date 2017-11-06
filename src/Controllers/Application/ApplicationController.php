<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\UserPermission;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ApplicationController implements ControllerInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $permissionRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->permissionRepo = $em->getRepository(UserPermission::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);

        return $this->withTemplate($request, $response, $this->template, [
            'application' => $application,
            'permissions' => $this->getPermissions($application)
        ]);
    }

    /**
     * @param Application $application
     *
     * @return array
     */
    private function getPermissions(Application $application)
    {
        $permissions = $this->permissionRepo->findBy(['application' => $application]);

        if ($org = $application->organization()) {
            $orgPermissions = $this->permissionRepo->findBy(['organization' => $org]);

            if ($orgPermissions) {
                $permissions = array_merge($orgPermissions);
            }
        }

        return $permissions;
    }
}
