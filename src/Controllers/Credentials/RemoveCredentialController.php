<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Credentials;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Credential;
use Hal\Core\Entity\Target;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveCredentialController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;

    private const MSG_SUCCESS = '"%s" credential removed.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var EntityRepository
     */
    private $targetRepository;

    /**
     * @param EntityManagerInterface $em
     * @param URI $uri
     */
    public function __construct(EntityManagerInterface $em, URI $uri)
    {
        $this->em = $em;
        $this->uri = $uri;

        $this->targetRepository = $em->getRepository(Target::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        /** @var Credential $credential */
        $credential = $request->getAttribute(Credential::class);
        $authorizations = $this->getAuthorizations($request);

        if ($credential->isInternal() && !$authorizations->isSuper()) {
            $message = sprintf('Cannot remove internal credential "%s". Contact the administrator.', $credential->name());
            $this->withFlash($request, Flash::ERROR, $message);

            return $this->withRedirectRoute($response, $this->uri, 'credential', ['credential' => $credential->id()]);
        }

        $targets = $this->targetRepository->findBy(['credential' => $credential]);

        foreach ($targets as $target) {
            $target->withCredential(null);
            $this->em->persist($target);
        }

        $this->em->remove($credential);
        $this->em->flush();

        // target caches must be manually flushed here, since they would contain a link to a removed entity
        $cache = $this->em->getCache();
        $cache->evictEntityRegion(Target::class);

        $this->withFlash($request, Flash::SUCCESS, sprintf(self::MSG_SUCCESS, $credential->name()));
        return $this->withRedirectRoute($response, $this->uri, 'credentials');
    }
}
