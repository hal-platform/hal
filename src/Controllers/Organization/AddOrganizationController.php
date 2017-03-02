<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Organization;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Utility\ValidatorTrait;
use QL\Hal\Core\Entity\Group;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class AddOrganizationController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;
    use ValidatorTrait;

    private const MSG_SUCCESS = 'Organization "%s" added.';

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
        $form = $this->getFormData($request);

        if ($organization = $this->handleForm($form, $request)) {
            $msg = sprintf(self::MSG_SUCCESS, $organization->name());
            $this->withFlash($request, Flash::SUCCESS, $msg);
            return $this->withRedirectRoute($response, $this->uri, 'applications');
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->errors,
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     *
     * @return Group|null
     */
    private function handleForm(array $data, ServerRequestInterface $request): ?Group
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $identifier = strtolower($data['identifier']);
        $name = $data['name'];

        $organization = $this->validateForm($identifier, $name);

        if ($organization) {
            $this->em->persist($organization);
            $this->em->flush();
        }

        return $organization;
    }

    /**
     * @param string $identifier
     * @param string $name
     *
     * @return Group|null
     */
    private function validateForm($identifier, $name)
    {
        $this->errors = array_merge(
            $this->validateSimple($identifier, 'Identifier', 24, true),
            $this->validateText($name, 'Name', 48, true)
        );

        if ($this->errors) return null;

        if ($org = $this->organizationRepo->findOneBy(['key' => $identifier])) {
            $this->errors[] = self::ERR_DUPE_IDENTIFIER;
        }

        if ($this->errors) return null;

        if ($org = $this->organizationRepo->findOneBy(['name' => $name])) {
            $this->errors[] = self::ERR_DUPE_NAME;
        }

        if ($this->errors) return null;

        $organization = (new Group)
            ->withKey($identifier)
            ->withName($name);

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
            'identifier' => $request->getParsedBody()['identifier'] ?? '',
            'name' => $request->getParsedBody()['name'] ?? ''
        ];

        return $form;
    }
}
