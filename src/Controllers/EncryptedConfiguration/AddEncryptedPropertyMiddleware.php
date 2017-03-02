<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\EncryptedConfiguration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Utility\ValidatorTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Crypto\Encrypter;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\EncryptedProperty;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\URI;

class AddEncryptedPropertyMiddleware implements MiddlewareInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;
    use ValidatorTrait;

    const MSG_SUCCESS = 'Encrypted Property "%s" added.';

    const ERR_NO_ENVIRONMENT = 'Please select an environment.';

    const ERR_DUPE = 'This property is already set for this environment.';
    const ERR_INVALID_PROPERTYNAME = 'Property name must consist of letters, numbers, and underscores only.';
    const ERR_INVALID_DATA = 'Data must not have newlines or tabs.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $encryptedRepo;
    private $envRepo;

    /**
     * @var Encrypter
     */
    private $encrypter;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var callable
     */
    private $random;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param EntityManagerInterface $em
     * @param Encrypter $encrypter
     * @param URI $uri
     * @param callable $random
     */
    public function __construct(
        EntityManagerInterface $em,
        Encrypter $encrypter,
        URI $uri,
        callable $random
    ) {
        $this->em = $em;
        $this->encryptedRepo = $em->getRepository(EncryptedProperty::class);
        $this->envRepo = $em->getRepository(Environment::class);

        $this->encrypter = $encrypter;
        $this->uri = $uri;
        $this->random = $random;

        $this->errors = [];
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($request->getMethod() !== 'POST') {
            return $next($request, $response);
        }

        $application = $request->getAttribute(Application::class);

        $form = [
            'environment' => $request->getParsedBody()['environment'] ?? '',
            'name' => $request->getParsedBody()['name'] ?? '',
            'decrypted' => $request->getParsedBody()['decrypted'] ?? ''
        ];

        $encrypted = $this->isValid($application, ...array_values($form));

        // if didn't create a property, add errors and pass through to controller
        if (!$encrypted) {
            return $next(
                $this->withContext($request, ['errors' => $this->errors]),
                $response
            );
        }

        // persist to database
        $this->em->persist($encrypted);
        $this->em->flush();

        $message = sprintf(self::MSG_SUCCESS, $encrypted->name());
        $this->withFlash($request, Flash::SUCCESS, $message);
        return $this->withRedirectRoute($response, $this->uri, 'encrypted.configuration', ['application' => $application->id()]);
    }

    /**
     * @param Application $application
     * @param string $environmentID
     * @param string $name
     * @param string $decrypted
     *
     * @return EncryptedProperty|null
     */
    private function isValid(Application $application, $environmentID, $name, $decrypted): ?EncryptedProperty
    {
        $this->errors = array_merge(
            $this->validateText('name', 'Property Name', '64', true),
            $this->validateText('decrypted', 'Value', '200', true)
        );

        // alphanumeric, underscore only for property names
        if (!preg_match('@^[0-9a-z_]+$@i', $name)) {
            $this->errors[] = self::ERR_INVALID_PROPERTYNAME;
        }

        // No weird shit in encrypted data
        if (preg_match('#[\t\n]+#', $decrypted) === 1) {
            $this->errors[] = self::ERR_INVALID_DATA;
        }

        if (!$environmentID) {
            $this->errors[] = self::ERR_NO_ENVIRONMENT;
        }

        $name = strtoupper($name);

        if ($this->errors) return null;

        $env = null;
        // verify environment
        if ($environmentID !== 'global') {
            if (!$env = $this->envRepo->find($environmentID)) {
                $this->errors[] = self::ERR_NO_ENVIRONMENT;
            }
        }

        if ($this->errors) return null;

        // check dupe
        if ($this->isPropertyDuplicate($name, $application, $env)) {
            $this->errors[] = self::ERR_DUPE;
        }

        if ($this->errors) return null;

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
        $encrypted = $this->encryptedRepo->findBy([
            'name' => $name,
            'application' => $application,
            'environment' => $env
        ]);

        return ($encrypted instanceof EncryptedProperty);
    }
}
