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
use Hal\Core\Entity\User;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class IdentityProviderController implements ControllerInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $userRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->userRepo = $em->getRepository(User::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $idp = $request->getAttribute(UserIdentityProvider::class);

        return $this->withTemplate($request, $response, $this->template, [
            'idp' => $idp,
            'can_remove' => $this->canIDPBeRemoved($idp)
        ]);
    }

    /**
     * @param UserIdentityProvider $idp
     *
     * @return bool
     */
    private function canIDPBeRemoved(UserIdentityProvider $idp)
    {
        $hasUsers = $this->userRepo->findOneBy(['provider' => $idp]);

        return ($hasUsers === null);
    }
}
