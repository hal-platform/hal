<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Credentials;

use Doctrine\ORM\EntityManagerInterface;
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

class AddCredentialController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'Credential "%s" added.';

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
     * @param CredentialValidator $credentialValidator
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        CredentialValidator $credentialValidator,
        URI $uri
    ) {
        $this->template = $template;
        $this->credentialValidator = $credentialValidator;
        $this->uri = $uri;

        $this->em = $em;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $form = $this->getFormData($request);

        if ($credential = $this->handleForm($form, $request)) {
            $this->withFlash($request, Flash::SUCCESS, sprintf(self::MSG_SUCCESS, $credential->name()));
            return $this->withRedirectRoute($response, $this->uri, 'credentials');
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->credentialValidator->errors(),

            'credential_options' => CredentialEnum::options()
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

        $credential = $this->credentialValidator->isValid(...array_values($data));

        if ($credential) {
            $this->em->persist($credential);
            $this->em->flush();
        }

        return $credential;
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

            'aws_account' => $request->getParsedBody()['aws_account'] ?? '',
            'aws_role' => $request->getParsedBody()['aws_role'] ?? '',

            'privatekey_username' => $request->getParsedBody()['privatekey_username'] ?? '',
            'privatekey_path' => $request->getParsedBody()['privatekey_path'] ?? '',
            'privatekey_file' => $request->getParsedBody()['privatekey_file'] ?? '',

            'is_internal' => $request->getParsedBody()['is_internal'] ?? ''
        ];

        return $form;
    }
}
