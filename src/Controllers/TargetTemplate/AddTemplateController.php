<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\TargetTemplate;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\AWS\AWSAuthenticator;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\TargetTemplate;
use Hal\Core\Parameters;
use Hal\Core\Type\TargetEnum;
use Hal\Core\Repository\EnvironmentRepository;
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

class AddTemplateController implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'Template "%s" added.';

    private const ERR_NO_ENVIRONMENTS = 'A template requires an environment. Environments must be added before any templates can be created.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EnvironmentRepository
     */
    private $environmentRepo;

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
        $this->environmentRepo = $em->getRepository(Environment::class);

        $this->validator = $validator;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        if (!$environments = $this->environmentRepo->getAllEnvironmentsSorted()) {
            $this->withFlashError($request, self::ERR_NO_ENVIRONMENTS);
            return $this->withRedirectRoute($response, $this->uri, 'templates');
        }

        $form = $this->getFormData($request);

        if ($template = $this->handleForm($form, $request)) {
            $this->withFlashSuccess($request, sprintf(self::MSG_SUCCESS, $template->name()));
            return $this->withRedirectRoute($response, $this->uri, 'templates');
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->validator->errors(),

            'environments' => $environments,

            'deployment_types' => TargetEnum::options(),
            'aws_regions' => AWSAuthenticator::$awsRegions,
            's3_methods' => Parameters::TARGET_S3_METHODS,
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     *
     * @return TargetTemplate|null
     */
    private function handleForm(array $data, ServerRequestInterface $request): ?TargetTemplate
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        if (!$this->isCSRFValid($request)) {
            return null;
        }

        $template = $this->validator->isValid($data['deployment_type'], $data);

        if ($template) {
            $this->em->persist($template);
            $this->em->flush();
        }

        return $template;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request)
    {
        $data = $request->getParsedBody();
        $type = $data['deployment_type'] ?? '';

        $form = [
            'deployment_type' => $type,
            'environment' => $data['environment'] ?? '',

            'name' => $data['name'] ?? '',

            'script_context' => $data['script_context'] ?? '',
            'credential' => $data['credential'] ?? '',
        ];

        return $form + $this->validator->getTemplateFormData($request, $type, null);
    }
}
