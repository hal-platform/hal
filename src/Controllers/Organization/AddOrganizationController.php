<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Organization;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Organization;
use Hal\Core\Entity\User;
use Hal\Core\Entity\UserPermission;
use Hal\Core\Type\UserPermissionEnum;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Security\AuthorizationService;
use Hal\UI\Validator\OrganizationValidator;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class AddOrganizationController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'Organization "%s" added.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var OrganizationValidator
     */
    private $orgValidator;

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
     * @param OrganizationValidator $orgValidator
     * @param AuthorizationService $authorizationService
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        OrganizationValidator $orgValidator,
        AuthorizationService $authorizationService,
        URI $uri
    ) {
        $this->template = $template;
        $this->em = $em;
        $this->orgValidator = $orgValidator;
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

        if ($organization = $this->handleForm($form, $request)) {
            $this->addOwnerPermissions($organization, $user);

            $msg = sprintf(self::MSG_SUCCESS, $organization->name());

            $this->withFlash($request, Flash::SUCCESS, $msg);
            return $this->withRedirectRoute($response, $this->uri, 'applications');
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->orgValidator->errors(),
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     *
     * @return Organization|null
     */
    private function handleForm(array $data, ServerRequestInterface $request): ?Organization
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $organization = $this->orgValidator->isValid($data['name'], $data['description']);
        if ($organization) {
            $this->em->persist($organization);
            $this->em->flush();
        }

        return $organization;
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
            'description' => $request->getParsedBody()['description'] ?? ''
        ];

        return $form;
    }

    /**
     * @param Organization $organization
     * @param User $user
     *
     * @return void
     */
    private function addOwnerPermissions(Organization $organization, User $user)
    {
        $permissions = (new UserPermission)
            ->withType(UserPermissionEnum::TYPE_OWNER)
            ->withUser($user)
            ->withOrganization($organization);

        // Add permissions and clear cache
        $this->authorizationService->addUserPermissions($permissions);
    }
}
