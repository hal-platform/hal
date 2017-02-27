<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Server;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Repository\DeploymentRepository;
use QL\Hal\Core\Type\EnumType\ServerEnum;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveServerController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;

    private const SUCCESS = '%s server removed.';
    private const ERR_DEPLOYMENTS = 'Cannot remove server. All associated deployments must first be removed.';

    /**
     * @var DeploymentRepository
     */
    private $deployRepo;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param EntityManagerInterface $em
     * @param URI $uri
     */
    public function __construct(EntityManagerInterface $em, URI $uri)
    {
        $this->deployRepo = $em->getRepository(Deployment::class);
        $this->em = $em;
        $this->uri = $uri;
    }

    /**
     * @inheritdoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $server = $request->getAttribute(Server::class);

        if ($this->deployRepo->findBy(['server' => $server])) {
            $this
                ->getFlash($request)
                ->withMessage(Flash::ERROR, self::ERR_DEPLOYMENTS);

            return $this->withRedirectRoute($response, $this->uri, 'server', ['server' => $server->id]);
        }

        $this->em->remove($server);
        $this->em->flush();

        $this
            ->getFlash($request)
            ->withMessage(Flash::SUCCESS, sprintf(self::SUCCESS, $server->formatHumanType()));

        return $this->withRedirectRoute($response, $this->uri, 'servers');
    }
}
