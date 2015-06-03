<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Middleware\ACL;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;
use QL\Hal\Middleware\ACL\LoginMiddleware;
use QL\Hal\Service\PermissionsService;
use QL\Kraken\Core\Entity\Application;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Slim\Halt;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Note: Admins and Supers also pass this middleware bouncer.
 */
class HalLeadMiddleware implements MiddlewareInterface
{
    /**
     * @type ContainerInterface
     */
    private $di;

    /**
     * @type LoginMiddleware
     */
    private $loginMiddleware;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type PermissionsService
     */
    private $permissions;

    /**
     * @type EntityRepository
     */
    private $applicationRepo;

    /**
     * @type Halt
     */
    private $halt;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param ContainerInterface $di
     * @param LoginMiddleware $loginMiddleware
     * @param TemplateInterface $template
     * @param PermissionsService $permissions
     * @param EntityManagerInterface $em
     * @param Halt $halt
     */
    public function __construct(
        ContainerInterface $di,
        LoginMiddleware $loginMiddleware,
        TemplateInterface $template,
        PermissionsService $permissions,
        EntityManagerInterface $em,
        Halt $halt,
        array $parameters
    ) {
        $this->di = $di;
        $this->loginMiddleware = $loginMiddleware;

        $this->template = $template;
        $this->permissions = $permissions;
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->halt = $halt;
        $this->parameters = $parameters;

    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function __invoke()
    {
        // Ensure user is logged in first
        call_user_func($this->loginMiddleware);

        $user = $this->di->get('currentUser');
        $perm = $this->permissions->getUserPermissions($user);

        if ($perm->isButtonPusher() || $perm->isSuper()) {
            return;
        }

        $application = isset($this->parameters['application']) ? $this->parameters['application'] : null;

        if ($application && $perm->isLead()) {
            $krakenApp = $this->applicationRepo->find($application);

            if ($krakenApp->halApplication()) {
                if (in_array($krakenApp->halApplication()->id(), $perm->leadApplications(), true)) {
                    return;
                }
            }
        }

        $rendered = $this->template->render();

        call_user_func($this->halt, 403, $rendered);
    }
}
