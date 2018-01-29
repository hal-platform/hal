<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin\IDP;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Repository\System\UserIdentityProviderRepository;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class IdentityProvidersController implements ControllerInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var UserIdentityProviderRepository
     */
    private $idpRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->idpRepo = $em->getRepository(UserIdentityProvider::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $this->withTemplate($request, $response, $this->template, [
            'id_providers' => $this->idpRepo->findAll(),
            'user_counts' => $this->idpRepo->getUserCounts()
        ]);
    }
}
