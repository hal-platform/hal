<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration;

use QL\Kraken\ACL;
use QL\Kraken\ConfigurationDiffService;
use QL\Kraken\Core\Entity\Target;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class DeployController implements ControllerInterface
{
    const ERR_ENCRYPTION_KEY = 'QKS Encryption key is missed. This must be added for this application in this environment.';

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
     * @type Target
     */
    private $target;

    /**
     * @type ConfigurationDiffService
     */
    private $diffService;

    /**
     * @param TemplateInterface $template
     * @param Target $target
     * @param ConfigurationDiffService $diffService
     */
    public function __construct(
        TemplateInterface $template,
        Target $target,
        ConfigurationDiffService $diffService
    ) {
        $this->template = $template;
        $this->target = $target;
        $this->diffService = $diffService;
    }

    /**
     * @return void
     */
    public function __invoke()
    {
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
