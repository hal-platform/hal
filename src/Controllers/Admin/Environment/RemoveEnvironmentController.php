<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin\Environment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Environment;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveEnvironmentController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;

    private const MSG_SUCCESS = '"%s" environment removed.';

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
        $this->em = $em;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $environment = $request->getAttribute(Environment::class);

        // @todo handle all entities that may depend on env:
        // - build, release
        // - target
        // - encrypted config
        // - scoped: (template target, credentials, permissions)

        $this->em->remove($environment);
        $this->em->flush();

        $this->withFlash($request, Flash::SUCCESS, sprintf(self::MSG_SUCCESS, $environment->name()));
        return $this->withRedirectRoute($response, $this->uri, 'environments');
    }
}
