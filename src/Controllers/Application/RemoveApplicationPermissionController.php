<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\User\UserPermission;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Validator\ValidatorErrorTrait;
use Hal\UI\Security\AuthorizationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class RemoveApplicationPermissionController implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;
    use ValidatorErrorTrait;

    private const MSG_SUCCESS = 'Permissions succesfully revoked!';
    private const MSG_NO_PERMS_TO_REVOKE = 'No permissions have been granted to this application.';

    private const ERR_NO_CHANGES = 'No changes have been made.';

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
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);

        $permissions = $this->getPermissions($application);
        if (!$permissions) {
            $this->withFlashError($request,self::MSG_NO_PERMS_TO_REVOKE);
            return $this->withRedirectRoute($response, $this->uri, 'application', ['application' => $application->id()]);
        }

        $form = $this->getFormData($request, $permissions);

        if ($itWorked = $this->handleForm($form, $request, $permissions)) {
            $this->withFlashSuccess($request, self::MSG_SUCCESS);
            return $this->withRedirectRoute($response, $this->uri, 'application', ['application' => $application->id()]);
        }

        return $this->withTemplate($request, $response, $this->template, [
            'application' => $application,
            'permissions' => $permissions,

            'form' => $form,
            'errors' => $this->errors()
        ]);
    }

    /**
     * Get all permissions to an application, excluding global (admins).
     *
     * @param Application $application
     *
     * @return array
     */
    private function getPermissions(Application $application)
    {
        $permissions = $this->em
            ->getRepository(UserPermission::class)
            ->findBy(['application' => $application]);

        usort($permissions, function ($a, $b) {
            $a = $a->user()->name();
            $b = $b->user()->name();
            return strcasecmp($a, $b);
        });

        return $permissions;
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     * @param array $existing
     *
     * @return bool
     */
    private function handleForm(array $data, ServerRequestInterface $request, array $existing)
    {
        if ($request->getMethod() !== 'POST') {
            return false;
        }

        if (!$this->isCSRFValid($request)) {
            return false;
        }

        $toRemove = [];
        foreach ($existing as $perm) {
            if (!in_array($perm->id(), $data['permissions'])) {
                $toRemove[] = $perm;
            }
        }

        # no changes made
        if (!$toRemove) {
            $this->addError(self::ERR_NO_CHANGES);
            return false;
        }

        # remove and flush cache
        foreach ($toRemove as $perm) {
            $this->authorizationService->removeUserPermissions($perm, true);
        }

        $this->em->flush();
        return true;
    }

    /**
     * @param ServerRequestInterface $request
     * @param array $existing
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request, array $existing)
    {
        if ($request->getMethod() === 'POST') {
            $data = $request->getParsedBody();

            $p = $data['permissions'] ?? [];
        } else {
            $p = array_map(function($v) {
                return $v->id();
                return strlen($v) !== 0;
            }, $existing);
        }

        $form = [
            'permissions' => is_array($p) ? $p : []
        ];

        return $form;
    }
}
