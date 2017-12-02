<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Credentials;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Crypto\Encryption;
use Hal\Core\Entity\Credential;
use Hal\Core\Type\CredentialEnum;
use Hal\UI\Flash;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Validator\CredentialValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class EditCredentialController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'Credential "%s" was updated.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var CredentialValidator
     */
    private $credentialValidator;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param CredentialValidator $credentialValidator
     * @param Encryption $encryption
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        CredentialValidator $credentialValidator,
        Encryption $encryption,
        URI $uri
    ) {
        $this->template = $template;
        $this->credentialValidator = $credentialValidator;
        $this->encryption = $encryption;
        $this->uri = $uri;

        $this->em = $em;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $credential = $request->getAttribute(Credential::class);

        $form = $this->getFormData($request, $credential);

        if ($modified = $this->handleForm($form, $request, $credential)) {
            $msg = sprintf(self::MSG_SUCCESS, $credential->name());

            $this->withFlash($request, Flash::SUCCESS, $msg);
            return $this->withRedirectRoute($response, $this->uri, 'credential', ['credential' => $modified->id()]);
        }

        return $this->withTemplate($request, $response, $this->template, [
            'credential' => $credential,
            'form' => $form,
            'errors' => $this->credentialValidator->errors(),

            'credential_types' => AddCredentialController::HUMAN_READABLE_TYPES
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     * @param Credential $credential
     *
     * @return Credential|null
     */
    private function handleForm(array $data, ServerRequestInterface $request, Credential $credential): ?Credential
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $credential = $this->credentialValidator->isEditValid($credential, ...array_values($data));

        if ($credential) {
            $this->em->persist($credential);
            $this->em->flush();
        }

        return $credential;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Credential $credential
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request, Credential $credential)
    {
        $isPost = ($request->getMethod() === 'POST');

        $type = $request->getParsedBody()['type'] ?? '';
        $name = $request->getParsedBody()['name'] ?? '';
        $isInternal = $request->getParsedBody()['is_internal'] ?? '';

        $key = $request->getParsedBody()['aws_key'] ?? '';
        $secret = $request->getParsedBody()['aws_secret'] ?? '';

        $account = $request->getParsedBody()['aws_account'] ?? '';
        $role = $request->getParsedBody()['aws_role'] ?? '';

        $username = $request->getParsedBody()['privatekey_username'] ?? '';
        $path = $request->getParsedBody()['privatekey_path'] ?? '';
        $file = $request->getParsedBody()['privatekey_file'] ?? '';

        $original = $this->getUneditedData($credential);

        $form = [
            'type' => $isPost ? $type : $credential->type(),
            'name' => $isPost ? $name : $credential->name(),

            'aws_key' => $isPost ? $key : $original['aws_key'],
            'aws_secret' => $isPost ? $secret : $original['aws_secret'],

            'aws_account' => $isPost ? $account : $original['aws_account'],
            'aws_role' => $isPost ? $role : $original['aws_role'],

            'privatekey_username' => $isPost ? $username : $original['privatekey_username'],
            'privatekey_path' => $isPost ? $path : $original['privatekey_path'],
            'privatekey_file' => $isPost ? $file : $original['privatekey_file'],

            'is_internal' => $isPost ? $isInternal : $credential->isInternal()
        ];

        return $form;
    }

    /**
     * @param Credential $credential
     *
     * @return array
     */
    private function getUneditedData(Credential $credential)
    {
        $data = [
            'aws_key' => '',
            'aws_secret' => '',

            'aws_account' => '',
            'aws_role' => '',

            'privatekey_username' => '',
            'privatekey_path' => '',
            'privatekey_file' => '',
        ];

        if ($credential->type() === CredentialEnum::TYPE_AWS_STATIC) {
            $secret = $credential->details()->secret();

            $data['aws_key'] = $credential->details()->key();
            $data['aws_secret'] = $secret ? $this->decrypt($secret) : '';
        }

        if ($credential->type() === CredentialEnum::TYPE_AWS_ROLE) {
            $data['aws_account'] = $credential->details()->account();
            $data['aws_role'] = $credential->details()->role();
        }

        if ($credential->type() === CredentialEnum::TYPE_PRIVATEKEY) {
            $file = $credential->details()->file();

            $data['privatekey_username'] = $credential->details()->username();
            $data['privatekey_path'] = $credential->details()->path();
            $data['privatekey_file'] = $file ? $this->decrypt($file) : '';
        }

        return $data;
    }

    /**
     * @param string $encrypted
     *
     * @return string
     */
    private function decrypt($encrypted)
    {
        try {
            $decrypted = $this->encryption->decrypt($encrypted);
            return $decrypted;

        } catch (Exception $ex) {
            return '';
        }
    }
}
