<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Group;
use QL\Hal\Core\Entity\UserSettings;
use QL\Hal\Core\Repository\ApplicationRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ApplicationsController implements ControllerInterface
{
    use SessionTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var ApplicationRepository
     */
    private $applicationRepo;

    /**
     * @var EntityRepository
     */
    private $groupRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;

        $this->applicationRepo = $em->getRepository(Application::class);
        $this->groupRepo = $em->getRepository(Group::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $user = $this->getUser($request);

        $grouped = $this->applicationRepo->getGroupedApplications();

        $groups = [];

        foreach ($this->groupRepo->findAll() as $group) {
            $groups[$group->id()] = $group;
        }

        $favorites = $this->findFavorites($grouped, $user->settings());

        return $this->withTemplate($request, $response, $this->template, [
            'favorites' => $favorites,
            'applications' => $grouped,
            'groups' => $groups
        ]);
    }

    /**
     * @param array $grouped
     * @param UserSettings|null $settings
     *
     * @return Application[]
     */
    private function findFavorites(array $grouped, ?UserSettings $settings)
    {
        if (!$settings) {
            return [];
        }

        $saved = array_fill_keys($settings->favoriteApplications(), true);
        $favorites = [];

        foreach ($grouped as $applications) {
            foreach ($applications as $application) {
                if (isset($saved[$application->id()])) {
                    $favorites[] = $application;
                }
            }
        }

        return $favorites;
    }
}
