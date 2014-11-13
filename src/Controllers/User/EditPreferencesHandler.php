<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use QL\Hal\Helpers\UrlHelper;
use QL\Panthor\Http\EncryptedCookies;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Slim;
use Twig_Template;

class EditPreferencesHandler
{
    /**
     * @var EncryptedCookies
     */
    private $cookies;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     * @var string
     */
    private $preferencesExpiry;

    /**
     *  @param EncryptedCookies $cookies
     *  @param UrlHelper $url
     *  @param string $preferencesExpiry
     */
    public function __construct(EncryptedCookies $cookies, UrlHelper $url, $preferencesExpiry)
    {
        $this->cookies = $cookies;
        $this->url = $url;
        $this->preferencesExpiry = $preferencesExpiry;
    }

    /**
     *  @param Request $request
     *  @param Response $response
     *  @param array $params
     *  @param callable $notFound
     */
    public function __invoke(Request $request, Response $response, array $params = null, callable $notFound = null)
    {
        $id = $params['id'];
        $nav = $request->post('nav');

        if (is_array($nav)) {
            $nav = implode(' ', $nav);
        }

        $this->cookies->setCookie('navpref', trim($nav), $this->preferencesExpiry);

        $this->url->redirectFor('settings');
    }
}
