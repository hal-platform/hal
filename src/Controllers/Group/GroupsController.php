<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace HAL\UI\Controllers\Group;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Group;
use Hal\Core\Repository\GroupRepository;
use Hal\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class GroupsController implements ControllerInterface
{
    use SortingTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var GroupRepository
     */
    private $groupRepository;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->em = $em;
        $this->groupRepository = $em->getRepository(Group::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $groups = $this->groupRepository->findAll();

        return $this->withTemplate($request, $response, $this->template, [
            'group_environments' => $this->sort($groups),
            'group_count' => count($groups)
        ]);
    }

    /**
     * @param Group[] $groups
     *
     * @return array
     */
    private function sort(array $groups)
    {
        $environments = [
            'dev' => [],
            'test' => [],
            'beta' => [],
            'prod' => []
        ];

        foreach ($groups as $group) {
            $env = $group->environment()->name();

            if (!array_key_exists($env, $environments)) {
                $environments[$env] = [];
            }

            $environments[$env][] = $group;
        }

        $sorter = $this->groupSorter();
        foreach ($environments as &$env) {
            usort($env, $sorter);
        }

        return $environments;
    }
}
