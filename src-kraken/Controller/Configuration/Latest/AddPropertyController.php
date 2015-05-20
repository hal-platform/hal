<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration\Latest;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Flasher;
use QL\Kraken\ConfigurationDiffService;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Environment;
use QL\Kraken\Entity\Target;
use QL\Kraken\Entity\Property;
use QL\Kraken\Entity\Schema;
use QL\Kraken\Validator\PropertyValidator;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Slim\NotFound;
use Slim\Http\Request;

class AddPropertyController implements ControllerInterface
{
    const SUCCESS = 'Property "%s" set.';

    /**
     * @type Request
     */
    private $request;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Application
     */
    private $application;

    /**
     * @type Environment
     */
    private $environment;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $targetRepo;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type ConfigurationDiffService
     */
    private $diffService;

    /**
     * @type PropertyValidator
     */
    private $validator;

    /**
     * @type NotFound
     */
    private $notFound;

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
        NotFound $notFound
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
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        if (!$target = $this->targetRepo->findOneBy(['application' => $this->application, 'environment' => $this->environment])) {
            return call_user_func($this->notFound);
        }

        if ($this->request->isPost()) {
            if ($property = $this->handleForm()) {
                // flash and redirect
                $this->flasher
                    ->withFlash(sprintf(self::SUCCESS, $property->schema()->key()), 'success')
                    ->load('kraken.configuration.latest', [
                        'application' => $this->application->id(),
                        'environment' => $this->environment->id()
                    ]);
            }
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
