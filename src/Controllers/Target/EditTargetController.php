<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Target;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Credential;
use Hal\Core\Entity\Target;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class EditTargetController implements ControllerInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $credentialRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em
    ) {
        $this->template = $template;
        $this->credentialRepo = $em->getRepository(Credential::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $target = $request->getAttribute(Target::class);

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $this->getFormData($request, $target),

            'application' => $application,
            'target' => $target,
            's3_methods' => Target::S3_METHODS,
            'credentials' => $this->credentialRepo->findBy([], ['name' => 'ASC'])
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     * @param Target $target
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request, Target $target)
    {
        if ($request->getMethod() === 'POST') {
            $form = [
                'name' => $request->getParsedBody()['name'] ?? '',
                'path' => $request->getParsedBody()['path'] ?? '',

                'cd_name' => $request->getParsedBody()['cd_name'] ?? '',
                'cd_group' => $request->getParsedBody()['cd_group'] ?? '',
                'cd_config' => $request->getParsedBody()['cd_config'] ?? '',

                'eb_name' => $request->getParsedBody()['eb_name'] ?? '',
                'eb_environment' => $request->getParsedBody()['eb_environment'] ?? '',

                's3_method' => $request->getParsedBody()['s3_method'] ?? '',
                's3_bucket' => $request->getParsedBody()['s3_bucket'] ?? '',
                's3_remote_path' => $request->getParsedBody()['s3_remote_path'] ?? '',
                's3_local_path' => $request->getParsedBody()['s3_local_path'] ?? '',

                'script_context' => $request->getParsedBody()['script_context'] ?? '',

                'url' => $request->getParsedBody()['url'] ?? '',
                'credential' => $request->getParsedBody()['credential'] ?? ''
            ];
        } else {
            $form = [
                'name' => $target->name(),
                'path' => $target->parameter(Target::PARAM_REMOTE_PATH),

                'cd_name' => $target->parameter(Target::PARAM_APP),
                'cd_group' => $target->parameter(Target::PARAM_GROUP),
                'cd_config' => $target->parameter(Target::PARAM_CONFIG),

                'eb_name' => $target->parameter(Target::PARAM_APP),
                'eb_environment' => $target->parameter(Target::PARAM_ENV),

                's3_method' => $target->parameter(Target::PARAM_S3_METHOD),
                's3_bucket' => $target->parameter(Target::PARAM_BUCKET),
                's3_remote_path' => $target->parameter(Target::PARAM_REMOTE_PATH),
                's3_local_path' => $target->parameter(Target::PARAM_LOCAL_PATH),

                'script_context' => $target->parameter(Target::PARAM_CONTEXT),

                'url' => $target->url(),
                'credential' => $target->credential() ? $target->credential()->id() : ''
            ];
        }

        return $form;
    }
}
