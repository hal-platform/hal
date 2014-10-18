<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User\Token;

use QL\Hal\Core\Entity\Repository\TokenRepository;
use Slim\Http\Request;
use Slim\Http\Response;
use MCP\Corp\Account\User as LdapUser;
use Doctrine\ORM\EntityManager;
use QL\Hal\Helpers\UrlHelper;
use QL\Hal\Core\Entity\Token;

/**
 * Allow a user to delete an API token
 */
class TokenDeleteController
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var TokenRepository
     */
    private $tokens;

    /**
     * @var LdapUser
     */
    private $user;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @param EntityManager $em
     * @param TokenRepository $tokens
     * @param LdapUser $user
     * @param UrlHelper $url
     */
    public function __construct(
        EntityManager $em,
        TokenRepository $tokens,
        LdapUser $user,
        UrlHelper $url
    ) {
        $this->em = $em;
        $this->tokens = $tokens;
        $this->user = $user;
        $this->url = $url;
    }
    /**
     * @param Request $request
     * @param Response $response
     * @param array $params
     * @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = null, callable $notFound = null)
    {
        $token = $this->tokens->findOneBy(['value' => $params['token']]);

        if (!$token instanceof Token) {
            return $notFound();
        }

        if ($token->getUser()->getId() !== $this->user->commonId()) {
            // no permission to delete, silently fail for the baddy
            $response->redirect($this->url->urlFor('user.current'), 303);
        }

        $this->em->remove($token);
        $this->em->flush();

        $response->redirect($this->url->urlFor('user.current'), 303);
    }
}