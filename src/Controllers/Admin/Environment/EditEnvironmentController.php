<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin\Environment;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Environment;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Validator\EnvironmentValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class EditEnvironmentController implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'Environment "%s" was updated.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EnvironmentValidator
     */
    private $validator;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param EnvironmentValidator $validator
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        EnvironmentValidator $validator,
        URI $uri
    ) {
        $this->template = $template;
        $this->em = $em;
        $this->validator = $validator;

        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $environment = $request->getAttribute(Environment::class);

        $form = $this->getFormData($request, $environment);

        if ($modified = $this->handleForm($form, $request, $environment)) {
            $this->withFlashSuccess($request, sprintf(self::MSG_SUCCESS, $environment->name()));
            return $this->withRedirectRoute($response, $this->uri, 'environment', ['environment' => $modified->id()]);
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->validator->errors(),

            'environment' => $environment,
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     * @param Environment $environment
     *
     * @return Environment|null
     */
    private function handleForm(array $data, ServerRequestInterface $request, Environment $environment): ?Environment
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        if (!$this->isCSRFValid($request)) {
            return null;
        }

        $environment = $this->validator->isEditValid($environment, $data['name'], $data['is_production']);

        if ($environment) {
            $this->em->merge($environment);
            $this->em->flush();
        }

        return $environment;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Environment $environment
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request, Environment $environment)
    {
        $isPost = ($request->getMethod() === 'POST');

        $name = $request->getParsedBody()['name'] ?? '';
        $isProd = $request->getParsedBody()['is_production'] ?? '';

        $form = [
            'name' => $isPost ? $name : $environment->name(),
            'is_production' => $isPost ? $isProd : $environment->isProduction(),
        ];

        return $form;
    }
}
