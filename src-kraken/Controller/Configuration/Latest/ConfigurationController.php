<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Controller\Configuration\Latest;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\ConfigurationDiffService;
use QL\Kraken\Core\Entity\Application;
use QL\Kraken\Core\Entity\Diff;
use QL\Kraken\Core\Entity\Environment;
use QL\Kraken\Core\Entity\Target;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Slim\NotFound;

class ConfigurationController implements ControllerInterface
{
    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var Application
     */
    private $application;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var EntityRepository
     */
    private $targetRepo;

    /**
     * @var ConfigurationDiffService
     */
    private $diffService;

    /**
     * @var NotFound
     */
    private $notFound;

    /**
     * @param TemplateInterface $template
     * @param Application $application
     * @param Environment $environment
     * @param EntityManagerInterface $em
     * @param ConfigurationDiffService $diffService
     * @param NotFound $notFound
     */
    public function __construct(
        TemplateInterface $template,
        Application $application,
        Environment $environment,
        EntityManagerInterface $em,
        ConfigurationDiffService $diffService,
        NotFound $notFound
    ) {
        $this->template = $template;
        $this->application = $application;
        $this->environment = $environment;
        $this->diffService = $diffService;

        $this->targetRepo = $em->getRepository(Target::CLASS);
        $this->notFound = $notFound;
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        if (!$target = $this->targetRepo->findOneBy(['application' => $this->application, 'environment' => $this->environment])) {
            return call_user_func($this->notFound);
        }

        $latest = $this->diffService->resolveLatestConfiguration($target->application(), $target->environment());

        $context = [
            'application' => $this->application,
            'environment' => $this->environment,
            'configuration' => $latest,
            'target' => $target,

            'is_missing_properties' => $this->isMissingProperties($latest),
            'deployed' => $target->configuration()
        ];

        $this->template->render($context);
    }

    /**
     * @param Diff[] $latest
     *
     * @return bool
     */
    private function isMissingProperties(array $latest)
    {
        foreach ($latest as $diff) {
            if (!$diff->property()) {
                return true;
            }
        }

        return false;
    }
}
