<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Server;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Repository\DeploymentRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ServerController implements ControllerInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;
    /**
     * @var DeploymentRepository
     */
    private $deployRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em
    ) {
        $this->template = $template;
        $this->deployRepo = $em->getRepository(Deployment::class);
    }

    /**
     * The primary action of this controller.
     *
     * Must return ResponseInterface.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $server = $request->getAttribute(Server::class);
        $deployments = $this->deployRepo->findBy(['server' => $server]);

        usort($deployments, function ($a, $b) {
            $appA = $a->application()->name();
            $appB = $b->application()->name();

            return strcasecmp($appA, $appB);
        });

        return $this->withTemplate($request, $response, $this->template, [
            'server' => $server,
            'deployments' => $deployments
        ]);
    }
}
