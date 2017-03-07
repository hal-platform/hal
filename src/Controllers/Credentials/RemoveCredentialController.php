<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Credentials;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Credential;
use QL\Hal\Core\Entity\Deployment;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveCredentialController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;

    private const MSG_SUCCESS = 'Credential "%s" removed.';

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
        $credential = $request->getAttribute(Credential::class);

        $this->em->remove($credential);
        $this->em->flush();

        // Deployment caches must be manually flushed here, since they would contain a link to a removed entity
        $cache = $this->em->getCache();
        $cache->evictEntityRegion(Deployment::class);

        $this->withFlash($request, Flash::SUCCESS, sprintf(self::MSG_SUCCESS, $credential->name()));
        return $this->withRedirectRoute($response, $this->uri, 'admin.credentials');
    }
}
