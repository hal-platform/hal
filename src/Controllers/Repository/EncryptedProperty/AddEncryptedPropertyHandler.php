<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository\EncryptedProperty;

use Doctrine\ORM\EntityManager;
use QL\Hal\Session;
use QL\Hal\Core\Crypto\Encrypter;
use QL\Hal\Core\Entity\EncryptedProperty;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Repository\EncryptedPropertyRepository;
use QL\Hal\Core\Entity\Repository\EnvironmentRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Helpers\ValidatorHelperTrait;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Twig\Context;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;

class AddEncryptedPropertyHandler implements MiddlewareInterface
{
    use ValidatorHelperTrait;

    const SUCCESS = 'Encrypted Property Added.';

    const ERR_NO_ENVIRONMENT = 'Please select an environment.';
    const ERR_NO_REPOSITORY = 'Invalid repository.';

    const ERR_DUPE = 'This property is already set for this environment.';
    const ERR_INVALID_PROPERTYNAME = 'Property name must consist of letters, numbers, and underscores only.';
    const ERR_INVALID_DATA = 'Data must not have newlines or tabs.';

    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @type EncryptedPropertyRepository
     */
    private $encryptedRepo;

    /**
     * @type RepositoryRepository
     */
    private $repoRepo;

    /**
     * @type EnvironmentRepository
     */
    private $envRepo;

    /**
     * @type Encrypter
     */
    private $encrypter;

    /**
     * @type Session
     */
    private $session;

    /**
     * @type Url
     */
    private $url;

    /**
     * @type Context
     */
    private $context;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param EntityManager $em
     * @param EncryptedPropertyRepository $encryptedRepo
     * @param RepositoryRepository $repoRepo
     * @param EnvironmentRepository $envRepo
     * @param Encrypter $encrypter
     *
     * @param Session $session
     * @param Url $url
     * @param Context $context
     * @param Request $request
     * @param array $parameters
     */
    public function __construct(
        EntityManager $em,
        EncryptedPropertyRepository $encryptedRepo,
        RepositoryRepository $repoRepo,
        EnvironmentRepository $envRepo,
        Encrypter $encrypter,
        Session $session,
        Url $url,
        Context $context,
        Request $request,
        array $parameters
    ) {
        $this->em = $em;
        $this->encryptedRepo = $encryptedRepo;
        $this->repoRepo = $repoRepo;
        $this->envRepo = $envRepo;
        $this->encrypter = $encrypter;

        $this->session = $session;
        $this->url = $url;
        $this->context = $context;
        $this->request = $request;
        $this->parameters = $parameters;

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
            $this->parameters['repository'],
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
        $this->session->flash(self::SUCCESS, 'success');
        $this->url->redirectFor('repository.encrypted', ['repository' => $this->parameters['repository']], [], 303);
    }

    /**
     * @param string $repositoryId
     * @param string $environmentId
     * @param string $name
     * @param string $decrypted
     *
     * @return EncryptedProperty|null
     */
    private function isValid($repositoryId, $environmentId, $name, $decrypted)
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

        if (!$environmentId) {
            $errors[] = self::ERR_NO_ENVIRONMENT;
        }

        $name = strtoupper($name);

        $env = null;
        // verify environment
        if (!$errors && $environmentId !== 'all') {
            if (!$env = $this->envRepo->find($environmentId)) {
                $errors[] = self::ERR_NO_ENVIRONMENT;
            }
        }

        // verify repository
        if (!$errors) {
            if (!$repo = $this->repoRepo->find($repositoryId)) {
                $errors[] = self::ERR_NO_REPOSITORY;
            }
        }

        // check dupe
        if (!$errors) {
            if ($this->isPropertyDuplicate($name, $repo, $env)) {
                $errors[] = self::ERR_DUPE;
            }
        }

        if ($errors) {
            $this->errors = $errors;
            return null;
        }

        $encrypted = $this->encrypter->encrypt($decrypted);

        $property = new EncryptedProperty;
        $property->setName($name);
        $property->setData($encrypted);

        $property->setRepository($repo);

        if ($env) {
            $property->setEnvironment($env);
        }

        return $property;
    }

    /**
     * @param string $name
     * @param Repository $repository
     * @param Environment|null $env
     *
     * @return bool
     */
    private function isPropertyDuplicate($name, Repository $repository, $env)
    {
        $enc = $this->encryptedRepo->findBy([
            'name' => $name,
            'repository' => $repository,
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
