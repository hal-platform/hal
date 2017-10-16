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
use Hal\Core\Entity\UserSettings;
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

        $orgs = [];

        foreach ($this->organizationRepo->findAll() as $org) {
            $orgs[$org->id()] = $org;
        }

        $favorites = $this->findFavorites($grouped, $user->settings());

        return $this->withTemplate($request, $response, $this->template, [
            'favorites' => $favorites,
            'applications' => $grouped,
            'organizations' => $orgs
        ]);
    }

    /**
     * @param array $grouped
     * @param UserSettings $settings
     *
     * @return Application[]
     */
    private function findFavorites(array $grouped, UserSettings $settings)
    {
        $saved = array_fill_keys($settings->favoriteApplications(), true);
        $favorites = [];

        foreach ($grouped as $applications) {
            foreach ($applications as $application) {
                if ($saved[$application->id()] ?? false) {
                    $favorites[] = $application;
                }
            }
        }

        return $favorites;
    }
}
