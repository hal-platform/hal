<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Environment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\Group;
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
    private const ERR_HAS_GROUPS = 'Cannot remove environment. All associated groups must first be removed.';

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
    private $groupRepository;

    /**
     * @param EntityManagerInterface $em
     * @param URI $uri
     */
    public function __construct(EntityManagerInterface $em, URI $uri)
    {
        $this->groupRepository = $em->getRepository(Group::class);
        $this->em = $em;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $environment = $request->getAttribute(Environment::class);

        if ($groups = $this->groupRepository->findBy(['environment' => $environment])) {
            $this->withFlash($request, Flash::ERROR, self::ERR_HAS_GROUPS);
            return $this->withRedirectRoute($response, $this->uri, 'environment', ['environment' => $environment->id()]);
        }

        $this->em->remove($environment);
        $this->em->flush();

        $this->withFlash($request, Flash::SUCCESS, sprintf(self::MSG_SUCCESS, $environment->name()));
        return $this->withRedirectRoute($response, $this->uri, 'environments');
    }
}
