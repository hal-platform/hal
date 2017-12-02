<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Environment;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Environment;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Hal\UI\Validator\EnvironmentValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class AddEnvironmentController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'Environment "%s" added.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param EnvironmentValidator $envValidator
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        EnvironmentValidator $envValidator,
        URI $uri
    ) {
        $this->template = $template;
        $this->em = $em;
        $this->envValidator = $envValidator;

        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $form = $this->getFormData($request);

        if ($environment = $this->handleForm($form, $request)) {
            $msg = sprintf(self::MSG_SUCCESS, $environment->name());

            $this->withFlash($request, Flash::SUCCESS, $msg);
            return $this->withRedirectRoute($response, $this->uri, 'environments');
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->envValidator->errors(),
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     *
     * @return Environment|null
     */
    private function handleForm(array $data, ServerRequestInterface $request): ?Environment
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $environment = $this->envValidator->isValid($data['name'], $data['is_production']);
        if ($environment) {
            $this->em->persist($environment);
            $this->em->flush();
        }

        return $environment;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request)
    {
        $form = [
            'name' => $request->getParsedBody()['name'] ?? '',
            'is_production' => $request->getParsedBody()['is_production'] ?? ''
        ];

        return $form;
    }
}
