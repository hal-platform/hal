<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Service\PermissionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\UserPermission;
use QL\Hal\Core\Entity\UserType;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class RemoveApplicationPermissionController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use TemplatedControllerTrait;
    use SessionTrait;

    const MSG_SUCCESS = 'Permissions succesfully revoked!';
    const MSG_NO_PERMS_TO_REVOKE = 'No permissions have been granted to this application.';

    const ERR_NO_CHANGES = 'No changes have been made.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var EntityRepository
     */
    private $permissionRepo;
    private $typeRepo;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param PermissionService $permissions
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        PermissionService $permissions,
        URI $uri
    ) {
        $this->template = $template;
        $this->em = $em;
        $this->permissions = $permissions;
        $this->uri = $uri;

        $this->permissionRepo = $em->getRepository(UserPermission::class);
        $this->typeRepo = $em->getRepository(UserType::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);

        $permissions = $this->getPermissions($application);
        if (!$permissions) {
            $this->withFlash($request, Flash::ERROR, self::MSG_NO_PERMS_TO_REVOKE);
            return $this->withRedirectRoute($response, $this->uri, 'application', ['application' => $application->id()]);
        }

        $form = [];

        if ($request->getMethod() === 'POST') {

            $p = $request->getParsedBody()['permissions'] ?? [];
            $form = [
                'permissions' => is_array($p) ? $p : []
            ];

            if ($itWorked = $this->handleForm($permissions, $form['permissions'])) {
                $this->withFlash($request, Flash::SUCCESS, self::MSG_SUCCESS);
                return $this->withRedirectRoute($response, $this->uri, 'application', ['application' => $application->id()]);
            }

        } else {
            $form['permissions'] = [];
            foreach ($permissions as $p) {
                $form['permissions'][] = $p['original']->id();
            }
        }

        return $this->withTemplate($request, $response, $this->template, [
            'application' => $application,
            'permissions' => $permissions,

            'form' => $form,
            'errors' => $this->errors
        ]);
    }

    /**
     * Get all permissions to an application, excluding global (admins).
     *
     * Returns data in this form:
     * [
     *    [
     *        id: user_id                       # hal or github ID
     *        login: username                   # hal or github username
     *        hal: lead | prod | non-prod       # optional, hal only
     *        original:                         # original permission entity
     *
     *        # may also include all properties from github API
     *    ],
     * ]
     *
     * @param Application $application
     *
     * @return array
     */
    private function getPermissions(Application $application)
    {
        $deployPermissions = $this->em
            ->getRepository(UserPermission::class)
            ->findBy(['application' => $application]);

        $leadPermissions = $this->em
            ->getRepository(UserType::class)
            ->findBy(['application' => $application]);

        $permissions = [];

        foreach ($deployPermissions as $p) {
            $permissions[] = [
                'type' => $p->isProduction() ? 'prod' : 'non-prod',
                'original' => $p
            ];
        }

        foreach ($leadPermissions as $p) {
            $permissions[] = [
                'type' => 'lead',
                'original' => $p
            ];
        }

        usort($permissions, function($a, $b) {
            $a = $a['original']->user()->handle();
            $b = $b['original']->user()->handle();
            return strcasecmp($a, $b);
        });

        return $permissions;
    }

    /**
     * @param array $existingPermissions
     * @param array $permissions
     *
     * @return bool
     */
    private function handleForm(array $existingPermissions, array $permissions)
    {
        $toRemove = [];
        foreach ($existingPermissions as $p) {
            $perm = $p['original'];

            if (!in_array($perm->id(), $permissions)) {
                $toRemove[] = $perm;
            }
        }

        # no changes made
        if (!$toRemove) {
            $this->errors[] = self::ERR_NO_CHANGES;
            return false;
        }

        # remove and flush cache
        foreach ($toRemove as $perm) {
            $this->permissions->clearUserCache($perm->user());
            $this->em->remove($perm);
        }

        $this->em->flush();
        return true;
    }
}
