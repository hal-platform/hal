<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User;

use QL\Hal\Helpers\UrlHelper;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Slim;
use Twig_Template;

class EditPreferencesHandle
{
    const COOKIE_TIME = '6 months';

    /**
     * @var Slim
     */
    private $slim;

    /**
     * @var UrlHelper
     */
    private $url;

    /**
     *  @param UrlHelper $url
     */
    public function __construct(Slim $slim, UrlHelper $url)
    {
        $this->slim = $slim;
        $this->url = $url;
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

        $this->slim->setCookie('navpref', trim($nav), static::COOKIE_TIME);

        $this->url->redirectFor('settings');
    }
}
