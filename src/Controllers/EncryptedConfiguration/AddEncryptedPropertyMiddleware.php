<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\EncryptedConfiguration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Validator\EncryptedPropertyValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Crypto\Encryption;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\EncryptedProperty;
use Hal\Core\Entity\Environment;
use Hal\Core\Repository\EnvironmentRepository;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\URI;

class AddEncryptedPropertyMiddleware implements MiddlewareInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    const MSG_SUCCESS = 'Encrypted Property "%s" added.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EncryptedPropertyValidator
     */
    private $validator;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param EntityManagerInterface $em
     * @param Encryption $encrypter
     * @param EncryptedPropertyValidator $validator
     * @param URI $uri
     */
    public function __construct(
        EntityManagerInterface $em,
        EncryptedPropertyValidator $validator,
        URI $uri
    ) {
        $this->em = $em;

        $this->validator = $validator;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($request->getMethod() !== 'POST') {
            return $next($request, $response);
        }

        if (!$this->isCSRFValid($request)) {
            return $next($request, $response);
        }

        $application = $request->getAttribute(Application::class);

        $form = [
            'environment' => $request->getParsedBody()['environment'] ?? '',
            'name' => $request->getParsedBody()['name'] ?? '',
            'decrypted' => $request->getParsedBody()['decrypted'] ?? '',
        ];

        $encrypted = $this->validator->isValid($application, ...array_values($form));

        // if didn't create a property, add errors and pass through to controller
        if (!$encrypted) {
            return $next($this->withContext($request, ['errors' => $this->validator->errors()]), $response);
        }

        // persist to database
        $this->em->persist($encrypted);
        $this->em->flush();

        $this->withFlashSuccess($request, sprintf(self::MSG_SUCCESS, $encrypted->name()));
        return $this->withRedirectRoute($response, $this->uri, 'encrypted.configuration', ['application' => $application->id()]);
    }
}
