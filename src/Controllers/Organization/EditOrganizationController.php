<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Organization;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Organization;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Validator\OrganizationValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class EditOrganizationController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'Organization "%s" was updated.';

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
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param OrganizationValidator $orgValidator
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        OrganizationValidator $orgValidator,
        URI $uri
    ) {
        $this->template = $template;
        $this->em = $em;
        $this->orgValidator = $orgValidator;

        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $organization = $request->getAttribute(Organization::class);

        $form = $this->getFormData($request, $organization);

        if ($modified = $this->handleForm($form, $request, $organization)) {
            $msg = sprintf(self::MSG_SUCCESS, $organization->name());

            $this->withFlash($request, Flash::SUCCESS, $msg);
            return $this->withRedirectRoute($response, $this->uri, 'organization', ['organization' => $modified->id()]);
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->orgValidator->errors(),

            'organization' => $organization
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     * @param Organization $organization
     *
     * @return Organization|null
     */
    private function handleForm(array $data, ServerRequestInterface $request, Organization $organization): ?Organization
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $organization = $this->orgValidator->isEditValid($organization, $data['name'], $data['description']);

        if ($organization) {
            $this->em->persist($organization);
            $this->em->flush();
        }

        return $organization;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Organization $organization
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request, Organization $organization): array
    {
        $isPost = ($request->getMethod() === 'POST');

        $name = $request->getParsedBody()['name'] ?? '';
        $description = $request->getParsedBody()['description'] ?? '';

        $form = [
            'name' => $isPost ? $name : $organization->identifier(),
            'description' => $isPost ? $description : $organization->name(),
        ];

        return $form;
    }
}
