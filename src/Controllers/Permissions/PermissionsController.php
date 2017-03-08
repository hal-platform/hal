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
use QL\Hal\Core\Entity\UserType;
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
class PermissionsController implements ControllerInterface
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
    private $userTypesRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;

        $this->userTypesRepo = $em->getRepository(UserType::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $types = $this->getTypes();

        // sort
        $sorter = $this->typeSorter();

        usort($types['pleb'], $sorter);
        usort($types['lead'], $sorter);
        usort($types['btn_pusher'], $sorter);
        usort($types['super'], $sorter);

        return $this->withTemplate($request, $response, $this->template, [
            'user_types' => $types
        ]);
    }

    /**
     * Get all user types in the whole db, collated into per-type buckets
     *
     * @return array
     */
    private function getTypes()
    {
        $userTypes = $this->userTypesRepo->findAll();

        $collated = [
            'pleb' => [],
            'lead' => [],
            'btn_pusher' => [],
            'super' => []
        ];

        foreach ($userTypes as $userType) {
            $type = $userType->type();

            $collated[$type][] = $userType;
        }

        return $collated;
    }

    /**
     * @return callable
     */
    private function typeSorter()
    {
        return function(UserType $a, UserType $b) {
            $a = $a->user()->name();
            $b = $b->user()->name();

            return strcasecmp($a, $b);
        };
    }
}
