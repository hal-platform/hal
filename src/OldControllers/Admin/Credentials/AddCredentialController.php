<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin\Credentials;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Flasher;
use QL\Hal\Core\Crypto\Encrypter;
use QL\Hal\Core\Entity\Credential;
use QL\Hal\Core\Entity\Credential\AWSCredential;
use QL\Hal\Core\Type\EnumType\CredentialEnum;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class AddCredentialController implements ControllerInterface
{
    const SUCCESS = 'Credential "%s" added.';

    const ERR_TYPE_REQUIRED = 'Type is required.';
    const ERR_AWS_ONLY = 'Private Key credentials are currently disabled.';
    const ERR_INVALID_NAME = 'Please enter a valid name less than 100 characters.';
    const ERR_DUPLICATE_NAME = 'Credentials with this name already exist.';

    const ERR_INVALID_KEY = 'AWS Access Key is required and must be less than 100 characters.';
    const ERR_INVALID_SECRET = 'AWS Secret is required.';

    const VALIDATE_NAME_REGEX = '/^[a-zA-Z0-9\:\-.\\ ]{2,100}$/';

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
     * @var Request
     */
    private $request;

    /**
     * @var Flasher
     */
    private $flasher;

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
     * @param Request $request
     * @param Flasher $flasher
     * @param callable $random
     * @param Encrypter $encrypter
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Request $request,
        Flasher $flasher,
        callable $random,
        Encrypter $encrypter
    ) {
        $this->template = $template;
        $this->request = $request;
        $this->flasher = $flasher;
        $this->random = $random;
        $this->encrypter = $encrypter;

        $this->em = $em;
        $this->credentialRepo = $em->getRepository(Credential::CLASS);

        $this->errors = [];
    }

    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        $form = $this->data();

        if ($credential = $this->handleForm($form)) {
            return $this->flasher
                ->withFlash(sprintf(self::SUCCESS, $credential->name()), 'success')
                ->load('admin.credentials');
        }

        $this->template->render([
            'form' => $form,
            'errors' => $this->errors
        ]);
    }

    /**
     * @param array $data
     *
     * @return Credential|null
     */
    private function handleForm(array $data)
    {
        if (!$this->request->isPost()) {
            return null;
        }

        $credential = $this->validateForm(
            $data['type'],
            $data['name'],
            $data['aws_key'],
            $data['aws_secret'],
            $data['private_username'],
            $data['private_path'],
            $data['private_file']
        );

        if ($credential) {
            // persist to database
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

        if ($this->errors) return;

        if ($dupe = $this->credentialRepo->findOneBy(['name' => $name])) {
            $this->errors[] = self::ERR_DUPLICATE_NAME;
        }

        if ($this->errors) return;

        $id = call_user_func($this->random);

        $encrypted = $this->encrypter->encrypt($secret);
        $aws = new AWSCredential($key, $encrypted);

        return (new Credential($id))
            ->withType($type)
            ->withName($name)
            ->withAWS($aws);
    }

    /**
     * @return array
     */
    private function data()
    {
        $form = [
            'type' => $this->request->post('type'),
            'name' => $this->request->post('name'),

            'aws_key' => $this->request->post('aws_key'),
            'aws_secret' => $this->request->post('aws_secret'),

            'private_username' => $this->request->post('private_username'),
            'private_path' => $this->request->post('private_path'),
            'private_file' => $this->request->post('private_file')
        ];

        return $form;
    }
}
