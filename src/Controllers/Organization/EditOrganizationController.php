<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Organization;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Utility\ValidatorTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Group;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class EditOrganizationController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;
    use ValidatorTrait;

    const MSG_SUCCESS = 'Organization updated successfully.';

    private const ERR_DUPE_IDENTIFIER = 'An organization with this identifier already exists.';
    private const ERR_DUPE_NAME = 'A group with this name already exists.';

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
    private $organizationRepo;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        URI $uri
    ) {
        $this->template = $template;

        $this->organizationRepo = $em->getRepository(Group::class);
        $this->em = $em;

        $this->uri = $uri;

        $this->errors = [];
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $organization = $request->getAttribute(Group::class);

        $form = $this->getFormData($request, $organization);

        if ($modified = $this->handleForm($form, $request, $organization)) {
            $this->withFlash($request, Flash::SUCCESS, self::MSG_SUCCESS);
            return $this->withRedirectRoute($response, $this->uri, 'organization', ['organization' => $modified->id()]);
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->errors,

            'organization' => $organization
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     * @param Group $organization
     *
     * @return Group|null
     */
    private function handleForm(array $data, ServerRequestInterface $request, Group $organization): ?Group
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $organization = $this->validateForm($organization, $data['identifier'], $data['name']);

        if ($organization) {
            $this->em->persist($organization);
            $this->em->flush();
        }

        return $organization;
    }

    /**
     * @param Group $organization
     * @param string $identifier
     * @param string $name
     *
     * @return Group|null
     */
    private function validateForm(Group $organization, $identifier, $name)
    {
        $identifier = strtolower($identifier);

        $this->errors = array_merge(
            $this->validateSimple($identifier, 'Identifier', 24, true),
            $this->validateText($name, 'Name', 48, true)
        );

        if ($this->errors) return null;

        // Only check duplicate nickname if it is being changed
        if ($identifier !== $organization->key()) {
            if ($dupe = $this->organizationRepo->findOneBy(['key' => $identifier])) {
                $this->errors[] = self::ERR_DUPE_IDENTIFIER;
            }
        }

        if ($this->errors) return null;

        // Only check duplicate name if it is being changed
        if ($name !== $organization->name()) {
            if ($dupe = $this->organizationRepo->findOneBy(['name' => $name])) {
                $this->errors[] = self::ERR_DUPE_NAME;
            }
        }

        if ($this->errors) return null;

        $organization = $organization
            ->withKey($identifier)
            ->withName($name);

        return $organization;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Group $group
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request, Group $group)
    {
        if ($request->getMethod() === 'POST') {
            $form = [
                'identifier' => $request->getParsedBody()['identifier'] ?? '',
                'name' => $request->getParsedBody()['name'] ?? ''
            ];
        } else {
            $form = [
                'identifier' => $group->key(),
                'name' => $group->name()
            ];
        }

        return $form;
    }
}
