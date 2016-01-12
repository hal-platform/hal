<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Middleware\ACL;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;
use QL\Hal\Middleware\ACL\LoginMiddleware;
use QL\Hal\Service\PermissionService;
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
     * @var ContainerInterface
     */
    private $di;

    /**
     * @var LoginMiddleware
     */
    private $loginMiddleware;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var PermissionService
     */
    private $permissions;

    /**
     * @var EntityRepository
     */
    private $applicationRepo;

    /**
     * @var Halt
     */
    private $halt;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param ContainerInterface $di
     * @param LoginMiddleware $loginMiddleware
     * @param TemplateInterface $template
     * @param PermissionService $permissions
     * @param EntityManagerInterface $em
     * @param Halt $halt
     */
    public function __construct(
        ContainerInterface $di,
        LoginMiddleware $loginMiddleware,
        TemplateInterface $template,
        PermissionService $permissions,
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
