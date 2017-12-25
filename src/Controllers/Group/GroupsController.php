<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Group;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\Group;
use Hal\Core\Repository\EnvironmentRepository;
use Hal\Core\Utility\SortingTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
     * @var EnvironmentRepository
     */
    private $environmentRepo;

    /**
     * @var EntityRepository
     */
    private $templateRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->em = $em;
        $this->environmentRepo = $em->getRepository(Environment::class);
        $this->templateRepo = $em->getRepository(Group::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $environments = $this->environmentRepo->getAllEnvironmentsSorted();
        $templates = $this->templateRepo->findAll();

        return $this->withTemplate($request, $response, $this->template, [
            'environments' => $environments,
            'templates_by_environment' => $this->sort($templates)
        ]);
    }

    /**
     * @param Group[] $templates
     *
     * @return array
     */
    private function sort(array $templates)
    {
        $environments = [];

        foreach ($templates as $template) {
            $envID = $template->environment()->id();

            if (!array_key_exists($envID, $environments)) {
                $environments[$envID] = [];
            }

            $environments[$envID][] = $template;
        }

        $sorter = $this->groupSorter();
        foreach ($environments as $env) {
            usort($env, $sorter);
        }

        return $environments;
    }
}
