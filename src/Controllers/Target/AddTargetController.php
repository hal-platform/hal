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
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Credential;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\TargetTemplate;
use Hal\Core\Repository\EnvironmentRepository;
use Hal\Core\Type\TargetEnum;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class AddTargetController implements ControllerInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $templateRepo;
    private $credentialRepo;

    /**
     * @var EnvironmentRepository
     */
    private $environmentRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;

        $this->credentialRepo = $em->getRepository(Credential::class);
        $this->templateRepo = $em->getRepository(TargetTemplate::class);
        $this->environmentRepo = $em->getRepository(Environment::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);

        $selectedEnvironment = $request->getAttribute('selected_environment');

        $environments = $templates = $credentials = [];

        if ($selectedEnvironment) {
            $credentials = $this->credentialRepo->findBy([], ['name' => 'ASC']);
            $templates = $this->templateRepo->findBy([], ['name' => 'ASC']);

        } else {
            $environments = $this->environmentRepo->getAllEnvironmentsSorted();
        }

        return $this->withTemplate($request, $response, $this->template, [
            'application' => $application,

            'environments' => $environments,
            'credentials' => $credentials,
            'templates' => $templates,

            'deployment_types' => TargetEnum::options(),
            'aws_regions' => AWSAuthenticator::$awsRegions,
            's3_methods' => Target::S3_METHODS
        ]);
    }
}
