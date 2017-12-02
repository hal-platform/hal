<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Group;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\Group;
use Hal\Core\Repository\TargetRepository;
use Hal\Core\Type\EnumType\GroupEnum;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveGroupController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;

    private const MSG_SUCCESS = '%s group removed.';
    private const ERR_DEPLOYMENTS = 'Cannot remove group. All associated targets must first be removed.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var TargetRepository
     */
    private $targetRepo;

    /**
     * @param EntityManagerInterface $em
     * @param URI $uri
     */
    public function __construct(EntityManagerInterface $em, URI $uri)
    {
        $this->em = $em;
        $this->uri = $uri;

        $this->targetRepo = $em->getRepository(Target::class);
    }

    /**
     * @inheritdoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $group = $request->getAttribute(Group::class);

        if ($this->targetRepo->findBy(['group' => $group])) {
            $this->withFlash($request, Flash::ERROR, self::ERR_DEPLOYMENTS);
            return $this->withRedirectRoute($response, $this->uri, 'group', ['group' => $group->id()]);
        }

        $this->em->remove($group);
        $this->em->flush();

        $this->withFlash($request, Flash::SUCCESS, sprintf(self::MSG_SUCCESS, $group->format(false)));
        return $this->withRedirectRoute($response, $this->uri, 'groups');
    }
}
