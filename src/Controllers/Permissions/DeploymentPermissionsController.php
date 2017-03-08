<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Permissions;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\UserPermission;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

/**
 * Super:
 *     Add any.
 *     Remove Lead, ButtonPusher
 *
 * ButtonPusher:
 *     Add Lead, ButtonPusher
 *     Remove Lead
 *
 */
class DeploymentPermissionsController implements ControllerInterface
{
    use SessionTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $userPermissionsRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;

        $this->userPermissionsRepo = $em->getRepository(UserPermission::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $permissions = $this->getPermissions();

        // sort
        $sorter = $this->typeSorter();

        usort($permissions['prod'], $sorter);
        usort($permissions['non_prod'], $sorter);

        return $this->withTemplate($request, $response, $this->template, [
            'user_permissions' => $permissions
        ]);
    }

    /**
     * Get all user permissions in the whole db, collated into per-type buckets
     *
     * @return array
     */
    private function getPermissions()
    {
        $userPermissions = $this->userPermissionsRepo->findAll();

        $collated = [
            'prod' => [],
            'non_prod' => []
        ];

        foreach ($userPermissions as $userPermission) {

            if ($userPermission->isProduction()) {
                $collated['prod'][] = $userPermission;

            } else {
                $collated['non_prod'][] = $userPermission;
            }
        }

        return $collated;
    }

    /**
     * @return callable
     */
    private function typeSorter()
    {
        return function(UserPermission $a, UserPermission $b) {
            $a = $a->user()->name();
            $b = $b->user()->name();

            return strcasecmp($a, $b);
        };
    }
}
