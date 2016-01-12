<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Controller\Configuration;

use QL\Kraken\ACL;
use QL\Kraken\ConfigurationDiffService;
use QL\Kraken\Core\Entity\Target;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class DeployController implements ControllerInterface
{
    const ERR_ENCRYPTION_KEY = 'QKS Encryption key is missing. This must be added for this application in this environment.';

    const ERR_CONSUL_SERVICE = 'Consul Service URL is missing.';
    const ERR_QKS_SERVICE = 'QKS Service URL is missing.';

    const ERR_QKS_KEY = 'QKS source encryption key is missing.';

    const ERR_QKS_CLIENT_ID = 'QKS Client ID is missing.';
    const ERR_QKS_CLIENT_SECRET = 'QKS Client Secret is missing.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var Target
     */
    private $target;

    /**
     * @var ConfigurationDiffService
     */
    private $diffService;

    /**
     * @var ACL
     */
    private $acl;

    /**
     * @param TemplateInterface $template
     * @param Target $target
     * @param ConfigurationDiffService $diffService
     * @param ACL $acl
     */
    public function __construct(
        TemplateInterface $template,
        Target $target,
        ConfigurationDiffService $diffService,
        ACL $acl
    ) {
        $this->template = $template;
        $this->target = $target;
        $this->diffService = $diffService;
        $this->acl = $acl;
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $this->acl->requireDeployPermissions($this->target->application(), $this->target->environment());

        $diffs = $this->diffService->resolveLatestConfiguration($this->target->application(), $this->target->environment());

        // @todo verify checksum against consul checksum
        $diffs = $this->diffService->diff($this->target->configuration(), $diffs);

        $context = [
            'target' => $this->target,
            'application' => $this->target->application(),
            'environment' => $this->target->environment(),
            'diffs' => $diffs,
            'errors' => $this->sanityCheck($this->target)
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
