<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\System\UserIdentityProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class SignInController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $idpRepo;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param URI $uri
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em, URI $uri)
    {
        $this->template = $template;
        $this->uri = $uri;

        $this->idpRepo = $em->getRepository(UserIdentityProvider::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $providers = $this->idpRepo->findAll();
        if (!$providers) {
            return $this->withRedirectRoute($response, $this->uri, 'hal_bootstrap');
        }

        return $this->withTemplate($request, $response, $this->template, [
            'id_providers' => $providers
        ]);
    }
}
