<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration\Latest;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\DataType\GUID;
use QL\Hal\Core\Entity\User;
use QL\Hal\FlashFire;
use QL\Kraken\ConfigurationDiffService;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Environment;
use QL\Kraken\Entity\Target;
use QL\Kraken\Entity\Property;
use QL\Kraken\Entity\Schema;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Slim\NotFound;
use Slim\Http\Request;

class AddPropertyController implements ControllerInterface
{
    const SUCCESS = 'Property "%s" set.';
    const ERR_VALUE_REQUIRED = 'Please enter a value.';
    const ERR_MISSING_SCHEMA = 'Please select a property.';
    const ERR_DUPLICATE_PROPERTY = 'This property is already set for this environment.';

    const ERR_INTEGER = 'Please enter a valid integer number.';
    const ERR_FLOAT = 'Please enter a valid number (must include decimal).';
    const ERR_BOOLEAN = 'Please enter a valid boolean flag value.';
    const ERR_LIST = 'Please enter a list of values.';

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
    private $schemaRepo;
    private $propertyRepo;
    private $targetRepo;

    /**
     * @type FlashFire
     */
    private $flashFire;

    /**
     * @type ConfigurationDiffService
     */
    private $diffService;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param Application $application
     * @param Environment $environment
     * @param User $currentUser
     *
     * @param EntityManagerInterface $em
     * @param FlashFire $flashFire
     * @param ConfigurationDiffService $diffService
     * @param NotFound $notFound
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        Application $application,
        Environment $environment,
        User $currentUser,
        EntityManagerInterface $em,
        FlashFire $flashFire,
        ConfigurationDiffService $diffService,
        NotFound $notFound
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->application = $application;
        $this->environment = $environment;
        $this->currentUser = $currentUser;

        $this->em = $em;
        $this->targetRepo = $this->em->getRepository(Target::CLASS);
        $this->schemaRepo = $this->em->getRepository(Schema::CLASS);
        $this->propertyRepo = $this->em->getRepository(Property::CLASS);

        $this->flashFire = $flashFire;
        $this->diffService = $diffService;
        $this->notFound = $notFound;

        $this->errors = [];
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
                $this->flashFire->fire(sprintf(self::SUCCESS, $property->schema()->key()), 'kraken.configuration.latest', 'success', [
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

            'errors' => $this->errors,
            'form' => [
                'prop' => $this->request->post('prop'),
                'value' => $this->request->post('value'),

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
        $propertyId = $this->request->post('prop');

        if (!$schema = $this->schemaRepo->find($propertyId)) {
            $this->errors[] = self::ERR_MISSING_SCHEMA;
        }

        if ($this->errors) return; // bomb

        $value = $this->validateValue($schema);

        if ($this->errors) return; // bomb

        // dupe check
        if ($dupe = $this->propertyRepo->findOneBy(['schema' => $schema, 'environment' => $this->environment])) {
            $this->errors[] = self::ERR_DUPLICATE_PROPERTY;
        }

        if ($this->errors) return; // bomb

        return $this->saveProperty($schema, $value);
    }

    /**
     * @param Schema $schema
     *
     * @return string|string[]
     */
    private function validateValue(Schema $schema)
    {
        $value = $this->request->post('value');

        // get explicit input if generic was not passed
        if ($value === null) {
            $value = $this->request->post('value_' . $schema->dataType());
        }

        if ($schema->dataType() === 'integer') {
            $value = str_replace(',', '', $value);

            if (preg_match('/^[\-]?[\d]+$/', $value) !== 1) {
                $this->errors[] = self::ERR_INTEGER;
            }

        } elseif ($schema->dataType() === 'float') {
            $value = str_replace(',', '', $value);

            if (preg_match('/^[\-]?[\d]+[\.][\d]+$/', $value) !== 1) {
                $this->errors[] = self::ERR_FLOAT;
            }

        } elseif ($schema->dataType() === 'bool') {
            if (!in_array($value, ['true', 'false'], true)) {
                $this->errors[] = self::ERR_BOOLEAN;
            }

        } elseif ($schema->dataType() === 'strings') {
            if (!is_array($value)) {
                $this->errors[] = self::ERR_LIST;
            }

        } else {
            // "string"
        }

        return $value;
    }

    /**
     * @param Schema $schema
     * @param string $value
     *
     * @return Property
     */
    private function saveProperty(Schema $schema, $value)
    {
        $uniq = GUID::create()->asHex();
        $uniq = strtolower($uniq);

        $encoded = $this->encode($schema, $value);

        $property = (new Property)
            ->withId($uniq)
            ->withValue($encoded)
            ->withSchema($schema)
            ->withApplication($this->application)
            ->withEnvironment($this->environment)
            ->withUser($this->currentUser);

        // persist to database
        $this->em->persist($property);
        $this->em->flush();

        return $property;
    }

    /**
     * @param Schema $schema
     * @param string $value
     *
     * @return string
     */
    private function encode(Schema $schema, $value)
    {
        if ($schema->dataType() === 'integer') {
            $value = (int) $value;

        } elseif ($schema->dataType() === 'float') {
            $value = (float) $value;

        } elseif ($schema->dataType() === 'bool') {
            $value = (bool) $value;

        } elseif ($schema->dataType() === 'strings') {
            // @todo

        } else {
            // "string"
            $value = (string) $value;
        }

        // @todo JSON_PRESERVE_ZERO_FRACTION - PHP 5.6.6
        $encoded = json_encode($value);

        if (false) {
            // @todo encrypt
        }

        return $encoded;
    }
}
