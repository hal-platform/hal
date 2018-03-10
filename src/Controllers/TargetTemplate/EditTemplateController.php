<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\TargetTemplate;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\AWS\AWSAuthenticator;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\TargetTemplate;
use Hal\Core\Parameters;
use Hal\Core\Type\TargetEnum;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Validator\TargetTemplateValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class EditTemplateController implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'Template updated successfully.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TargetTemplateValidator
     */
    private $validator;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param TargetTemplateValidator $validator
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        TargetTemplateValidator $validator,
        URI $uri
    ) {
        $this->template = $template;

        $this->em = $em;

        $this->validator = $validator;
        $this->uri = $uri;
    }

    /**
     * @inheritdoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $template = $request->getAttribute(TargetTemplate::class);

        $form = $this->getFormData($request, $template);

        if ($modified = $this->handleForm($form, $request, $template)) {
            $this->withFlashSuccess($request, self::MSG_SUCCESS);
            return $this->withRedirectRoute($response, $this->uri, 'template', ['template' => $template->id()]);
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->validator->errors(),

            'template' => $template,

            'deployment_types' => TargetEnum::options(),
            'aws_regions' => AWSAuthenticator::$awsRegions,
            's3_methods' => Parameters::TARGET_S3_METHODS
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     * @param TargetTemplate $template
     *
     * @return TargetTemplate|null
     */
    private function handleForm(array $data, ServerRequestInterface $request, TargetTemplate $template)
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        if (!$this->isCSRFValid($request)) {
            return null;
        }

        $template = $this->validator->isEditValid($template, $data);

        if ($template) {
            $this->em->merge($template);
            $this->em->flush();
        }

        return $template;
    }

    /**
     * @param ServerRequestInterface $request
     * @param TargetTemplate $template
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request, TargetTemplate $template)
    {
        $data = $request->getParsedBody();

        if ($request->getMethod() !== 'POST') {
            // $data['credential'] = $target->credential() ? $target->credential()->id() : '';

            $data['name'] = $template->name();
            $data['script_context'] = $template->parameter(Parameters::TARGET_CONTEXT);
        }

        $type = $template->type();

        $form = [
            'deployment_type' => $type,

            'name' => $data['name'] ?? '',

            'script_context' => $data['script_context'] ?? '',
            'credential' => $data['credential'] ?? ''
        ];

        return $form + $this->validator->getTemplateFormData($request, $type, $template);
    }
}
