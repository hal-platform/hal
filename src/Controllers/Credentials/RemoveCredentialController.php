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
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveCredentialController implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;

    private const MSG_SUCCESS = '"%s" credential removed.';
    private const MSG_ERR_INTERNAL = 'Cannot remove internal credential "%s". Contact the administrator.';

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
        $credential = $request->getAttribute(Credential::class);
        $authorizations = $this->getAuthorizations($request);

        if (!$this->isCSRFValid($request)) {
            $this->withFlashError($request, $this->CSRFError());
            return $this->withRedirectRoute($response, $this->uri, 'credential', ['credential' => $credential->id()]);
        }

        if ($credential->isInternal() && !$authorizations->isSuper()) {
            $this->withFlashError($request, sprintf(self::MSG_ERR_INTERNAL, $credential->name()));
            return $this->withRedirectRoute($response, $this->uri, 'credential', ['credential' => $credential->id()]);
        }

        $targets = $this->targetRepository->findBy(['credential' => $credential]);

        foreach ($targets as $target) {
            $target->withCredential(null);
            $this->em->persist($target);
        }

        $this->em->remove($credential);
        $this->em->flush();

        $this->withFlashSuccess($request, sprintf(self::MSG_SUCCESS, $credential->name()));
        return $this->withRedirectRoute($response, $this->uri, 'credentials');
    }
}
