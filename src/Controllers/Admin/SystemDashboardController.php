<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin;

use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class SystemDashboardController implements ControllerInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @param TemplateInterface $template
     * @param string $encryptionKey
     * @param string $sessionEncryptionKey
     * @param string $halDeploymentFile
     */
    public function __construct(
        TemplateInterface $template,
    ) {
        $this->template = $template;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        # get hal push file if possible.
        $deploymentFile = file_exists($this->halDeploymentFile) ? file_get_contents($this->halDeploymentFile) : '';

        return $this->withTemplate($request, $response, $this->template, [
            'server_name' => gethostname(),
        ]);
    }
}
