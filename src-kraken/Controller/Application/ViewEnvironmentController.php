<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Application;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use MCP\DataType\GUID;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Encryption;
use QL\Kraken\Entity\Environment;
use QL\Kraken\Entity\Property;
use QL\Kraken\Entity\PropertySchema;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Url;
use QL\Panthor\Slim\NotFound;
use QL\Hal\Session;
use Slim\Http\Request;

class ViewEnvironmentController implements ControllerInterface
{
    const SUCCESS = 'Property "%s" added.';
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
     * @type EntityManager
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $encRepository;
    private $propRepository;

    /**
     * @type Url
     */
    private $url;

    /**
     * @type Session
     */
    private $session;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param Application $application
     *
     * @param $em
     *
     * @param Url $url
     * @param Session $session
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        Application $application,
        $em,
        Url $url,
        Session $session,
        NotFound $notFound,
        array $parameters
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->application = $application;

        $this->em = $em;
        $this->encRepository = $this->em->getRepository(Encryption::CLASS);
        $this->schemaRepository = $this->em->getRepository(PropertySchema::CLASS);
        $this->propRepository = $this->em->getRepository(Property::CLASS);

        $this->url = $url;
        $this->session = $session;
        $this->notFound = $notFound;
        $this->parameters = $parameters;

        $this->errors = [];
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        if (!$environment = $this->getEnvironment()) {
            return call_user_func($this->notFound);
        }

        if ($this->request->isPost()) {
            $this->handleForm($environment);
        }

        $configuration = $this->buildConfiguration($environment);

        $schema = [];
        foreach ($configuration as $config) {
            if ($config instanceof PropertySchema) {
                $schema[] = $config;
            }
        }

        $context = [
            'application' => $this->application,
            'environment' => $environment,
            'configuration' => $configuration,

            'missing_schema' => $schema,

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
     * @return Environment|null
     */
    private function getEnvironment()
    {
        $encryption = $this->encRepository->findOneBy([
            'environment' => $this->parameters['environment'],
            'application' => $this->application,
        ]);

        if ($encryption) {
            return $encryption->environment();
        }
    }

    /**
     * @todo this should be cached heavily
     *
     * @param Environment $environment
     *
     * @return Property|PropertySchema[]
     */
    private function buildConfiguration($environment)
    {
        $configuration = [];

        $schema = $this->schemaRepository->findBy([
            'application' => $this->application
        ], ['key' => 'ASC']);

        $properties = $this->propRepository->findBy([
            'application' => $this->application,
            'environment' => $environment
        ]);

        foreach ($schema as $schema) {
            $configuration[$schema->id()] = $schema;
        }

        foreach ($properties as $property) {
            $configuration[$property->propertySchema()->id()] = $property;
        }

        return $configuration;
    }

    /**
     * @param Environment $environment
     *
     * @return void
     */
    private function handleForm(Environment $environment)
    {
        $propertyId = $this->request->post('prop');

        if (!$schema = $this->schemaRepository->find($propertyId)) {
            $this->errors[] = self::ERR_MISSING_SCHEMA;
        }

        if ($this->errors) return; // bomb

        $value = $this->validateValue($schema);

        if ($this->errors) return; // bomb

        // dupe check
        if ($dupe = $this->propRepository->findOneBy(['propertySchema' => $schema, 'environment' => $environment])) {
            $this->errors[] = self::ERR_DUPLICATE_PROPERTY;
        }

        if ($this->errors) return; // bomb

        $this->saveProperty($environment, $schema, $value);
    }

    /**
     * @param PropertySchema $schema
     *
     * @return string|string[]
     */
    private function validateValue(PropertySchema $schema)
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
     * @param Environment $env
     * @param PropertySchema $schema
     * @param string $value
     *
     * @return void
     */
    private function saveProperty(Environment $env, PropertySchema $schema, $value)
    {
        $uniq = GUID::create()->asHex();
        $uniq = strtolower($uniq);

        $encoded = $this->encode($schema, $value);

        $property = (new Property)
            ->withId($uniq)
            ->withValue($encoded)
            ->withPropertySchema($schema)
            ->withApplication($this->application)
            ->withEnvironment($env);

        // persist to database
        $this->em->persist($property);
        $this->em->flush();

        // flash and redirect
        $this->session->flash(sprintf(self::SUCCESS, $schema->key()), 'success');
        $this->url->redirectFor('kraken.application.environment', [
            'id' => $this->application->id(),
            'environment' => $env->id()
        ]);
    }

    /**
     * @param PropertySchema $schema
     * @param string $value
     *
     * @return string
     */
    private function encode(PropertySchema $schema, $value)
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

        $encoded = json_encode($value);

        if (false) {
            // @todo encrypt
        }

        return $encoded;
    }
}
