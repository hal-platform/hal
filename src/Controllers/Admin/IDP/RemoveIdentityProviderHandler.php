<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin\IDP;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Entity\User\UserIdentity;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveIdentityProviderHandler implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;

    private const MSG_SUCCESS = '"%s" identity provider removed.';
    private const ERR_CANNOT_REMOVE = '"%s" identity provider cannot be removed.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $userIdentityRepo;

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

        $this->userIdentityRepo = $em->getRepository(UserIdentity::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $idp = $request->getAttribute(UserIdentityProvider::class);

        if (!$this->isCSRFValid($request)) {
            $this->withFlashError($request, $this->CSRFError());
            return $this->withRedirectRoute($response, $this->uri, 'id_provider', ['system_idp' => $idp->id()]);
        }

        // prevent if has users
        if (!$this->canIDPBeRemoved($idp)) {
            $this->withFlashError($request, sprintf(self::ERR_CANNOT_REMOVE, $idp->name()));
            return $this->withRedirectRoute($response, $this->uri, 'id_provider', ['system_idp' => $idp->id()]);
        }

        $this->em->remove($idp);
        $this->em->flush();

        $this->withFlashSuccess($request, sprintf(self::MSG_SUCCESS, $idp->name()));
        return $this->withRedirectRoute($response, $this->uri, 'id_providers');
    }

    /**
     * @param UserIdentityProvider $idp
     *
     * @return bool
     */
    private function canIDPBeRemoved(UserIdentityProvider $idp)
    {
        $hasUsers = $this->userIdentityRepo->findOneBy(['provider' => $idp]);

        return ($hasUsers === null);
    }
}
