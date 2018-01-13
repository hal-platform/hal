<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Target;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\AWS\AWSAuthenticator;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Credential;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\TargetTemplate;
use Hal\Core\Type\TargetEnum;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
    private $templateRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;

        $this->credentialRepo = $em->getRepository(Credential::class);
        $this->templateRepo = $em->getRepository(TargetTemplate::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $target = $request->getAttribute(Target::class);

        $credentials = $this->credentialRepo->findBy([], ['name' => 'ASC']);
        $templates = $this->templateRepo->findBy(['environment' => $target->environment()], ['name' => 'ASC']);

        return $this->withTemplate($request, $response, $this->template, [
            'application' => $application,
            'target' => $target,

            'credentials' => $credentials,
            'templates' => $templates,

            'deployment_types' => TargetEnum::options(),
            'aws_regions' => AWSAuthenticator::$awsRegions,
            's3_methods' => Target::S3_METHODS
        ]);
    }
}
