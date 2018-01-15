<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Auth;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\HTTP\CookieHandler;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class SignInController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use TemplatedControllerTrait;

    private const IDP_COOKIE_NAME = 'idp';
    private const IDP_COOKIE_EXPIRES = '3 months';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $idpRepo;

    /**
     * @var CookieHandler
     */
    private $cookies;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param CookieHandler $cookies
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        CookieHandler $cookies,
        URI $uri
    ) {
        $this->template = $template;
        $this->cookies = $cookies;
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

        $selectedIDP = $this->getSelectedIDP($request, $providers);
        $response = $this->saveSelectedIDP($response, $selectedIDP);

        return $this->withTemplate($request, $response, $this->template, [
            'id_providers' => $providers,

            'selected_idp' => $selectedIDP
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     * @param array $providers
     *
     * @return UserIdentityProvider|null
     */
    private function getSelectedIDP(ServerRequestInterface $request, array $providers)
    {
        if (count($providers) === 1) {
            return $providers[0];
        }

        // first check if idp in query
        $selectedIDP = $request->getQueryParams()['idp'] ?? '';

        // if not in query, check cookie
        if (!$selectedIDP) {
            $selectedIDP = $this->cookies->getCookie($request, self::IDP_COOKIE_NAME);
        }

        foreach ($providers as $idp) {
            if ($selectedIDP === $idp->id()) {
                return $idp;
            }
        }

        return null;
    }

    /**
     * @param ResponseInterface $response
     * @param UserIdentityProvider|null
     *
     * @return ResponseInterface
     */
    private function saveSelectedIDP(ResponseInterface $response, ?UserIdentityProvider $idp)
    {
        if (!$idp) {
            return $this->cookies->expireCookie($response, self::IDP_COOKIE_NAME);
        }

        return $this->cookies->withCookie($response, self::IDP_COOKIE_NAME, $idp->id(), self::IDP_COOKIE_EXPIRES);
    }
}
