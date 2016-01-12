<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Controller\Configuration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Flasher;
use QL\Kraken\ACL;
use QL\Kraken\ConfigurationDiffService;
use QL\Kraken\Core\Entity\Configuration;
use QL\Kraken\Core\Entity\Target;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class RollbackController implements ControllerInterface
{
    const ERR_ENCRYPTION_KEY = 'QKS Encryption key is missing. This must be added for this application in this environment.';

    const ERR_CONSUL_SERVICE = 'Consul Service URL is missing.';
    const ERR_QKS_SERVICE = 'QKS Service URL is missing.';

    const ERR_QKS_KEY = 'QKS source encryption key is missing.';

    const ERR_QKS_CLIENT_ID = 'QKS Client ID is missing.';
    const ERR_QKS_CLIENT_SECRET = 'QKS Client Secret is missing.';

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Configuration
     */
    private $configuration;

    /**
     * @type ConfigurationDiffService
     */
    private $diffService;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type EntityRepository
     */
    private $targetRepo;

    /**
     * @type ACL
     */
    private $acl;

    /**
     * @param TemplateInterface $template
     * @param Configuration $configuration
     * @param ConfigurationDiffService $diffService
     * @param Flasher $flasher
     * @param EntityManagerInterface $em
     * @param ACL $acl
     */
    public function __construct(
        TemplateInterface $template,
        Configuration $configuration,
        ConfigurationDiffService $diffService,
        Flasher $flasher,
        EntityManagerInterface $em,
        ACL $acl
    ) {
        $this->template = $template;
        $this->configuration = $configuration;
        $this->diffService = $diffService;
        $this->flasher = $flasher;
        $this->acl = $acl;

        $this->targetRepo = $em->getRepository(Target::CLASS);
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $this->acl->requireDeployPermissions($this->configuration->application(), $this->configuration->environment());

        $target = $this->targetRepo->findOneBy([
            'application' => $this->configuration->application(),
            'environment' => $this->configuration->environment()
        ]);

        if (!$target) {
            return $this->flasher
                ->withFlash('Cannot rollback to this configuration. The deployment target seems to have been removed.', 'error')
                ->load('kraken.configuration', ['configuration' => $this->configuration->id()]);
        }

        // Build up the Diffs from the old snapshot
        $diffs = $this->diffService->resolveConfiguration($this->configuration);

        // Compare old snapshot to active configuration
        // Only if there is an active configuration, and its not the same as what we're trying to redeploy
        if ($target->configuration() && $target->configuration() !== $this->configuration) {
            $diffs = $this->diffService->diff($target->configuration(), $diffs);
        }

        $context = [
            'configuration' => $this->configuration,
            'application' => $this->configuration->application(),
            'environment' => $this->configuration->environment(),
            'target' => $target,
            'diffs' => $diffs,
            'errors' => $this->sanityCheck($target)
        ];

        $this->template->render($context);
    }

    /**
     * Sanity check to make sure consul/qks/target is configured correctly and can be deployed.
     *
     * Returns a list of configuration errors.
     *
     * @param Target $target
     *
     * @return string[]
     */
    private function sanityCheck(Target $target)
    {
        $errors = [];

        $environment = $target->environment();

        if (!$target->key()) $errors[] = self::ERR_ENCRYPTION_KEY;

        if (!$environment->consulServiceURL()) $errors[] = self::ERR_CONSUL_SERVICE;
        if (!$environment->qksServiceURL()) $errors[] = self::ERR_QKS_SERVICE;
        if (!$environment->qksEncryptionKey()) $errors[] = self::ERR_QKS_KEY;
        if (!$environment->qksClientID()) $errors[] = self::ERR_QKS_CLIENT_ID;
        if (!$environment->qksClientSecret()) $errors[] = self::ERR_QKS_CLIENT_SECRET;

        return $errors;
    }
}
