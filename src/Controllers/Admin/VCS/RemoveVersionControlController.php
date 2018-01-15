<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin\VCS;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\System\VersionControlProvider;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveVersionControlController implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;

    private const MSG_SUCCESS = '"%s" version control system removed.';
    private const ERR_CANNOT_REMOVE = '"%s" version control system cannot be removed.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $applicationRepo;

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

        $this->applicationRepo = $em->getRepository(Application::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $vcs = $request->getAttribute(VersionControlProvider::class);

        if (!$this->isCSRFValid($request)) {
            $this->withFlashError($request, $this->CSRFError());
            return $this->withRedirectRoute($response, $this->uri, 'vcs_provider', ['system_vcs' => $vcs->id()]);
        }

        // prevent if has applications
        if (!$this->canVCSBeRemoved($vcs)) {
            $this->withFlashError($request, sprintf(self::ERR_CANNOT_REMOVE, $vcs->name()));
            return $this->withRedirectRoute($response, $this->uri, 'vcs_provider', ['system_vcs' => $vcs->id()]);
        }

        $this->em->remove($vcs);
        $this->em->flush();

        $this->withFlashSuccess($request, sprintf(self::MSG_SUCCESS, $vcs->name()));
        return $this->withRedirectRoute($response, $this->uri, 'vcs_providers');
    }

    /**
     * @param VersionControlProvider $vcs
     *
     * @return bool
     */
    private function canVCSBeRemoved(VersionControlProvider $vcs)
    {
        $hasApplications = $this->applicationRepo->findOneBy(['provider' => $vcs]);

        return ($hasApplications === null);
    }
}
