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
use Hal\Core\Entity\System\VersionControlProvider;
use Hal\Core\Entity\User\UserPermission;
use Hal\Core\Type\UserPermissionEnum;
use Hal\Core\Utility\SortingTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Security\AuthorizationService;
use Hal\UI\Validator\ApplicationValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class AddApplicationController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use SortingTrait;
    use TemplatedControllerTrait;

    const MSG_SUCCESS = 'Application "%s" added.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $applicationRepo;
    private $organizationRepo;
    private $vcsRepo;

    /**
     * @var ApplicationValidator
     */
    private $applicationValidator;

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
     * @param ApplicationValidator $applicationValidator
     * @param AuthorizationService $authorizationService
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        ApplicationValidator $applicationValidator,
        AuthorizationService $authorizationService,
        URI $uri
    ) {
        $this->template = $template;

        $this->applicationRepo = $em->getRepository(Application::class);
        $this->organizationRepo = $em->getRepository(Organization::class);
        $this->vcsRepo = $em->getRepository(VersionControlProvider::class);
        $this->em = $em;

        $this->applicationValidator = $applicationValidator;
        $this->authorizationService = $authorizationService;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $user = $this->getUser($request);
        $form = $this->getFormData($request);

        if ($application = $this->handleForm($form, $request)) {
            $this->addOwnerPermissions($application, $user);

            $msg = sprintf(self::MSG_SUCCESS, $application->name());

            $this->withFlash($request, Flash::SUCCESS, $msg);
            return $this->withRedirectRoute($response, $this->uri, 'applications');
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->applicationValidator->errors(),

            'organizations' => $this->getOrganizations(),
            'vcs' => $this->vcsRepo->findAll()
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     *
     * @return Application|null
     */
    private function handleForm(array $data, ServerRequestInterface $request): ?Application
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $application = $this->applicationValidator->isValid(
            $data['name'],
            $data['organization'],
            $data['vcs_provider']
        );

        if ($application && $application->provider()) {
            $application = $this->applicationValidator->isVCSValid($application, $data);
        }

        if ($application) {
            $this->em->persist($application);
            $this->em->flush();
        }

        return $application;
    }

    /**
     * @return array
     */
    private function getOrganizations()
    {
        $orgs = $this->organizationRepo->findAll();
        usort($orgs, $this->organizationSorter());

        return $orgs;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request): array
    {
        $form = [
            'name' => $request->getParsedBody()['name'] ?? '',

            'organization' => $request->getParsedBody()['organization'] ?? '',
            'vcs_provider' => $request->getParsedBody()['vcs_provider'] ?? '',

            'gh_owner' => $request->getParsedBody()['gh_owner'] ?? '',
            'gh_repo' => $request->getParsedBody()['gh_repo'] ?? '',
            'git_link' => $request->getParsedBody()['git_link'] ?? '',
        ];

        return $form;
    }

    /**
     * @param Application $application
     * @param User $user
     *
     * @return void
     */
    private function addOwnerPermissions(Application $application, User $user)
    {
        $permissions = (new UserPermission)
            ->withType(UserPermissionEnum::TYPE_OWNER)
            ->withUser($user)
            ->withApplication($application);

        // Add permissions and clear cache
        $this->authorizationService->addUserPermissions($permissions);
    }
}
