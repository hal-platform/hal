<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Organization;
use Hal\Core\Entity\User;
use Hal\Core\Repository\ApplicationRepository;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
    private $organizationRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;

        $this->applicationRepo = $em->getRepository(Application::class);
        $this->organizationRepo = $em->getRepository(Organization::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $user = $this->getUser($request);

        $grouped = $this->applicationRepo->getGroupedApplications();
        $organizations = $this->getOrganizations();

        $favorites = $this->findFavorites($user, $grouped);

        return $this->withTemplate($request, $response, $this->template, [
            'favorites' => $favorites,
            'applications' => $grouped,
            'organizations' => $organizations
        ]);
    }

    /**
     * @return array
     */
    private function getOrganizations()
    {
        $orgs = [];
        foreach ($this->organizationRepo->findAll() as $org) {
            $orgs[$org->id()] = $org;
        }

        return $orgs;
    }

    /**
     * @param User $settings
     * @param array $grouped
     *
     * @return Application[]
     */
    private function findFavorites(User $user, array $groupApps)
    {
        if (!$favorites = $user->settings('favorite_applications')) {
            return [];
        }

        $saved = array_fill_keys($favorites, true);
        $favorites = [];

        foreach ($groupApps as $applications) {
            foreach ($applications as $application) {
                if ($saved[$application->id()] ?? false) {
                    $favorites[] = $application;
                }
            }
        }

        return $favorites;
    }
}
