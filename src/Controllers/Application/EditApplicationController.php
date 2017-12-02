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
use Hal\Core\Utility\SortingTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Validator\ApplicationValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class EditApplicationController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use SortingTrait;
    use TemplatedControllerTrait;

    const MSG_SUCCESS = 'Application "%s" was updated.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $applicationRepo;
    private $organizationRepo;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ApplicationValidator
     */
    private $applicationValidator;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param ApplicationValidator $applicationValidator
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        ApplicationValidator $applicationValidator,
        URI $uri
    ) {
        $this->template = $template;

        $this->applicationRepo = $em->getRepository(Application::class);
        $this->organizationRepo = $em->getRepository(Organization::class);
        $this->em = $em;

        $this->applicationValidator = $applicationValidator;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);

        $form = $this->getFormData($request, $application);

        if ($modified = $this->handleForm($form, $request, $application)) {
            $message = sprintf(self::MSG_SUCCESS, $application->name());

            $this->withFlash($request, Flash::SUCCESS, $message);
            return $this->withRedirectRoute($response, $this->uri, 'application', ['application' => $modified->id()]);
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->applicationValidator->errors(),

            'application' => $application,
            'organizations' => $this->getOrganizations()
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     * @param Application $application
     *
     * @return Application|null
     */
    private function handleForm(array $data, ServerRequestInterface $request, Application $application): ?Application
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $application = $this->applicationValidator->isEditValid(
            $application,
            $data['name'],
            $data['description'],
            $data['github'],
            $data['organization']
        );

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
     * @param Application $application
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request, Application $application)
    {
        $isPost = ($request->getMethod() === 'POST');

        $name = $request->getParsedBody()['name'] ?? '';
        $description = $request->getParsedBody()['description'] ?? '';
        $organization = $request->getParsedBody()['organization'] ?? '';
        $github = $request->getParsedBody()['github'] ?? '';

        $originalOrganizationID = $application->organization() ? $application->organization()->id() : '';
        $originalGitHub = sprintf('%s/%s', $application->gitHub()->owner(), $application->gitHub()->repository());

        $form = [
            'name' => $isPost ? $name : $application->identifier(),
            'description' => $isPost ? $description : $application->name(),
            'organization' => $isPost ? $organization : $originalOrganizationID,
            'github' => $isPost ? $github : $originalGitHub,
        ];

        return $form;
    }
}
