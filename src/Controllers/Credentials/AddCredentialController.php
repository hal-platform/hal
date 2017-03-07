<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Credentials;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Flash;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Crypto\Encrypter;
use QL\Hal\Core\Entity\Credential;
use QL\Hal\Core\Entity\Credential\AWSCredential;
use QL\Hal\Core\Type\EnumType\CredentialEnum;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class AddCredentialController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'Credential "%s" added.';

    private const ERR_TYPE_REQUIRED = 'Type is required.';
    private const ERR_AWS_ONLY = 'Private Key credentials are currently disabled.';
    private const ERR_INVALID_NAME = 'Please enter a valid name less than 100 characters.';
    private const ERR_DUPLICATE_NAME = 'Credentials with this name already exist.';

    private const ERR_INVALID_KEY = 'AWS Access Key is required and must be less than 100 characters.';
    private const ERR_INVALID_SECRET = 'AWS Secret is required.';

    private const VALIDATE_NAME_REGEX = '/^[a-zA-Z0-9\:\-.\\ ]{2,100}$/';

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
    private $credentialRepo;

    /**
     * @var callable
     */
    private $random;

    /**
     * @var Encrypter
     */
    private $encrypter;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param URI $uri
     * @param callable $random
     * @param Encrypter $encrypter
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        URI $uri,
        callable $random,
        Encrypter $encrypter
    ) {
        $this->template = $template;
        $this->uri = $uri;
        $this->random = $random;
        $this->encrypter = $encrypter;

        $this->em = $em;
        $this->credentialRepo = $em->getRepository(Credential::class);

        $this->errors = [];
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $form = $this->getFormData($request);

        if ($credential = $this->handleForm($form, $request)) {
            $this->withFlash($request, Flash::SUCCESS, sprintf(self::MSG_SUCCESS, $credential->name()));
            return $this->withRedirectRoute($response, $this->uri, 'admin.credentials');
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->errors
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     *
     * @return Credential|null
     */
    private function handleForm(array $data, ServerRequestInterface $request): ?Credential
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $credential = $this->validateForm(...array_values($data));

        if ($credential) {
            $this->em->persist($credential);
            $this->em->flush();
        }

        return $credential;
    }

    /**
     * @param string $type
     * @param string $name
     * @param string $key
     * @param string $secret
     * @param string $username
     * @param string $path
     * @param string $file
     *
     * @return Credential|null
     */
    private function validateForm($type, $name, $key, $secret, $username, $path, $file)
    {
        if (!in_array($type, CredentialEnum::values(), true)) {
            $this->errors[] = self::ERR_TYPE_REQUIRED;
        }

        if ($type && $type !== 'aws') {
            $this->errors[] = self::ERR_AWS_ONLY;
        }

        // name
        if (preg_match(self::VALIDATE_NAME_REGEX, $name) !== 1) {
            $this->errors[] = self::ERR_INVALID_NAME;
        }


        // aws
        if (preg_match('#[\t\n]+#', $key) === 1) {
            $this->errors[] = self::ERR_INVALID_KEY;
        }

        if (preg_match('#[\t\n]+#', $secret) === 1) {
            $this->errors[] = self::ERR_INVALID_SECRET;
        }

        $keylen = strlen($key);
        if ($keylen < 1 || $keylen > 100) {
            $this->errors[] = self::ERR_INVALID_KEY;
        }

        if (strlen($secret) < 1) {
            $this->errors[] = self::ERR_INVALID_SECRET;
        }

        if ($this->errors) return null;

        if ($dupe = $this->credentialRepo->findOneBy(['name' => $name])) {
            $this->errors[] = self::ERR_DUPLICATE_NAME;
        }

        if ($this->errors) return null;

        $id = call_user_func($this->random);

        $encrypted = $this->encrypter->encrypt($secret);
        $aws = new AWSCredential($key, $encrypted);

        return (new Credential($id))
            ->withType($type)
            ->withName($name)
            ->withAWS($aws);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request)
    {
        $form = [
            'type' => $request->getParsedBody()['type'] ?? '',
            'name' => $request->getParsedBody()['name'] ?? '',

            'aws_key' => $request->getParsedBody()['aws_key'] ?? '',
            'aws_secret' => $request->getParsedBody()['aws_secret'] ?? '',

            'private_username' => $request->getParsedBody()['private_username'] ?? '',
            'private_path' => $request->getParsedBody()['private_path'] ?? '',
            'private_file' => $request->getParsedBody()['private_file'] ?? ''
        ];

        return $form;
    }
}
