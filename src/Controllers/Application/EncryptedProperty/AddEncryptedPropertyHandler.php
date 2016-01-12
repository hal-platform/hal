<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Application\EncryptedProperty;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Flasher;
use QL\Hal\Core\Crypto\Encrypter;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\EncryptedProperty;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Hal\Utility\ValidatorTrait;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Twig\Context;
use Slim\Http\Request;

class AddEncryptedPropertyHandler implements MiddlewareInterface
{
    use ValidatorTrait;

    const SUCCESS = 'Encrypted Property "%s" added.';

    const ERR_NO_ENVIRONMENT = 'Please select an environment.';

    const ERR_DUPE = 'This property is already set for this environment.';
    const ERR_INVALID_PROPERTYNAME = 'Property name must consist of letters, numbers, and underscores only.';
    const ERR_INVALID_DATA = 'Data must not have newlines or tabs.';

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $encryptedRepo;
    private $envRepo;

    /**
     * @type Encrypter
     */
    private $encrypter;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type Context
     */
    private $context;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type callable
     */
    private $random;

    /**
     * @type Application
     */
    private $application;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param EntityManagerInterface $em
     * @param Encrypter $encrypter
     *
     * @param Flasher $flasher
     * @param Context $context
     * @param Request $request
     * @param callable $random
     *
     * @param Application $application
     */
    public function __construct(
        EntityManagerInterface $em,
        Encrypter $encrypter,
        Flasher $flasher,
        Context $context,
        Request $request,
        callable $random,

        Application $application
    ) {
        $this->em = $em;
        $this->encryptedRepo = $em->getRepository(EncryptedProperty::CLASS);
        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->encrypter = $encrypter;

        $this->flasher = $flasher;
        $this->context = $context;
        $this->request = $request;
        $this->random = $random;

        $this->application = $application;

        $this->errors = [];
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$this->request->isPost()) {
            return;
        }

        $encryptedProperty = $this->isValid(
            $this->application,
            $this->request->post('environment'),
            $this->request->post('name'),
            $this->request->post('decrypted')
        );

        // if didn't create a property, add errors and pass through to controller
        if (!$encryptedProperty) {
            $this->context->addContext([
                'errors' => $this->errors()
            ]);

            return;
        }

        // persist to database
        $this->em->persist($encryptedProperty);
        $this->em->flush();

        // flash and redirect
        $this->flasher
            ->withFlash(sprintf(self::SUCCESS, $encryptedProperty->name()), 'success')
            ->load('encrypted', ['application' => $this->application->id()]);
    }

    /**
     * @param Application $application
     * @param string $environmentID
     * @param string $name
     * @param string $decrypted
     *
     * @return EncryptedProperty|null
     */
    private function isValid(Application $application, $environmentID, $name, $decrypted)
    {
        // validate fields
        $errors = [];

        $errors = array_merge(
            $this->validateText('name', 'Property Name', '64', true),
            $this->validateText('decrypted', 'Value', '200', true)
        );

        // alphanumeric, underscore only for property names
        if (!preg_match('@^[0-9a-z_]+$@i', $name)) {
            $errors[] = self::ERR_INVALID_PROPERTYNAME;
        }

        // No weird shit in encrypted data
        if (preg_match('#[\t\n]+#', $decrypted) === 1) {
            $errors[] = self::ERR_INVALID_DATA;
        }

        if (!$environmentID) {
            $errors[] = self::ERR_NO_ENVIRONMENT;
        }

        $name = strtoupper($name);

        $env = null;
        // verify environment
        if (!$errors && $environmentID !== 'global') {
            if (!$env = $this->envRepo->find($environmentID)) {
                $errors[] = self::ERR_NO_ENVIRONMENT;
            }
        }

        // check dupe
        if (!$errors) {
            if ($this->isPropertyDuplicate($name, $application, $env)) {
                $errors[] = self::ERR_DUPE;
            }
        }

        if ($errors) {
            $this->errors = $errors;
            return null;
        }

        $encrypted = $this->encrypter->encrypt($decrypted);

        $id = call_user_func($this->random);
        $property = (new EncryptedProperty($id))
            ->withName($name)
            ->withData($encrypted)
            ->withApplication($application);

        if ($env) {
            $property->withEnvironment($env);
        }

        return $property;
    }

    /**
     * @param string $name
     * @param Application $application
     * @param Environment|null $env
     *
     * @return bool
     */
    private function isPropertyDuplicate($name, Application $application, Environment $env = null)
    {
        $enc = $this->encryptedRepo->findBy([
            'name' => $name,
            'application' => $application,
            'environment' => $env
        ]);

        if ($enc) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    private function errors()
    {
        return $this->errors;
    }
}
