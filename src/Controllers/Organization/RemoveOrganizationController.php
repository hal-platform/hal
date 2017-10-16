<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Organization;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\Organization;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveOrganizationController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;

    const MSG_SUCCESS = '"%s" organization removed.';
    const ERR_HAS_APPLICATIONS = 'Cannot remove organization. All associated applications must first be removed.';

    /**
     * @var EntityRepository
     */
    private $applicationRepo;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

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
        $this->applicationRepo = $em->getRepository(Application::class);
        $this->em = $em;

        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $organization = $request->getAttribute(Organization::class);

        if ($this->applicationRepo->findBy(['organization' => $organization])) {
            $this->withFlash($request, Flash::ERROR, self::ERR_HAS_APPLICATIONS);
            return $this->withRedirectRoute($response, $this->uri, 'organization', ['organization' => $organization->id()]);
        }

        $this->em->remove($organization);
        $this->em->flush();

        $this->withFlash($request, Flash::SUCCESS, sprintf(self::MSG_SUCCESS, $organization->name()));
        return $this->withRedirectRoute($response, $this->uri, 'applications');
    }
}
