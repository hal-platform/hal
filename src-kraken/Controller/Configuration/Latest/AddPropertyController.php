<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Controller\Configuration\Latest;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Flasher;
use QL\Kraken\ACL;
use QL\Kraken\ConfigurationDiffService;
use QL\Kraken\Core\Entity\Application;
use QL\Kraken\Core\Entity\Environment;
use QL\Kraken\Core\Entity\Target;
use QL\Kraken\Core\Entity\Property;
use QL\Kraken\Core\Entity\Schema;
use QL\Kraken\Validator\PropertyValidator;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Slim\NotFound;
use Slim\Http\Request;

class AddPropertyController implements ControllerInterface
{
    const SUCCESS = 'Property "%s" set.';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var Application
     */
    private $application;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var User
     */
    private $currentUser;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $targetRepo;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var ConfigurationDiffService
     */
    private $diffService;

    /**
     * @var PropertyValidator
     */
    private $validator;

    /**
     * @var NotFound
     */
    private $notFound;

    /**
     * @var ACL
     */
    private $acl;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param Application $application
     * @param Environment $environment
     * @param User $currentUser
     *
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param ConfigurationDiffService $diffService
     * @param PropertyValidator $validator
     * @param NotFound $notFound
     * @param ACL $acl
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        Application $application,
        Environment $environment,
        User $currentUser,

        EntityManagerInterface $em,
        Flasher $flasher,
        ConfigurationDiffService $diffService,
        PropertyValidator $validator,
        NotFound $notFound,
        ACL $acl
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->application = $application;
        $this->environment = $environment;
        $this->currentUser = $currentUser;

        $this->em = $em;
        $this->targetRepo = $this->em->getRepository(Target::CLASS);

        $this->flasher = $flasher;
        $this->diffService = $diffService;
        $this->validator = $validator;
        $this->notFound = $notFound;
        $this->acl = $acl;
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        if (!$target = $this->targetRepo->findOneBy(['application' => $this->application, 'environment' => $this->environment])) {
            return call_user_func($this->notFound);
        }

        $this->acl->requireDeployPermissions($this->application, $this->environment);

        if ($property = $this->handleForm()) {
            // flash and redirect
            $this->flasher
                ->withFlash(sprintf(self::SUCCESS, $property->schema()->key()), 'success')
                ->load('kraken.configuration.latest', [
                    'application' => $this->application->id(),
                    'environment' => $this->environment->id()
                ]);
        }

        $latest = $this->diffService->resolveLatestConfiguration($target->application(), $target->environment());
        $missing = $this->getMissingProperties($latest);

        $context = [
            'application' => $this->application,
            'environment' => $this->environment,

            'missing_schema' => $missing,

            'errors' => $this->validator->errors(),
            'form' => [
                'prop' => $this->request->post('prop'),
                'value' => $this->request->post('value'),
                'use_xl_string' => ($this->request->post('value_string_xl') === '1'),

                // explicit
                'value_string' => $this->request->post('value_string'),
                'value_strings' => $this->request->post('value_strings'),
                'value_bool' => $this->request->post('value_bool'),
                'value_int' => $this->request->post('value_int'),
                'value_float' => $this->request->post('value_float')
            ]
        ];

        $this->template->render($context);
    }

    /**
     * @param Diff[] $latest
     *
     * @return Schema[]
     */
    private function getMissingProperties(array $latest)
    {
        $schema = [];

        foreach ($latest as $diff) {
            if (!$diff->property()) {
                $schema[] = $diff->schema();
            }
        }

        return $schema;
    }

    /**
     * @return Property|null
     */
    private function handleForm()
    {
        if (!$this->request->isPost()) {
            return null;
        }

        $schemaId = $this->request->post('prop');

        if ($property = $this->validator->isValid($this->environment, $this->request, $schemaId)) {
            $property->withUser($this->currentUser);

            // persist to database
            $this->em->persist($property);
            $this->em->flush();

            return $property;
        }

        return null;
    }
}
