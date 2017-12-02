<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Security\AuthorizationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\UserPermission;
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
     * @var AuthorizationService
     */
    private $authorizationService;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var EntityRepository
     */
    private $userPermissionsRepository;
    private $typeRepo;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param AuthorizationService $authorizationService
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        AuthorizationService $authorizationService,
        URI $uri
    ) {
        $this->template = $template;
        $this->em = $em;
        $this->authorizationService = $authorizationService;
        $this->uri = $uri;

        $this->userPermissionsRepository = $em->getRepository(UserPermission::class);
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
                $form['permissions'][] = $p->id();
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
        $applicationPermissions = $this->em
            ->getRepository(UserPermission::class)
            ->findBy(['application' => $application]);

        usort($applicationPermissions, function($a, $b) {
            $a = $a->user()->username();
            $b = $b->user()->username();
            return strcasecmp($a, $b);
        });

        return $applicationPermissions;
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
        foreach ($existingPermissions as $perm) {

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
            $this->authorizationService->removeUserPermissions($perm, true);
        }

        $this->em->flush();
        return true;
    }
}
