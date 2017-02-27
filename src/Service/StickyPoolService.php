<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Service;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\HTTP\CookieHandler;
use QL\Panthor\Utility\JSON;

class StickyPoolService
{
    const COOKIE_NAME = 'stickypool';

    /**
     * @var CookieHandler
     */
    private $cookies;

    /**
     * @var JSON
     */
    private $json;

    /**
     * @var string
     */
    private $preferencesExpiry;

    /**
     * @param CookieHandler $cookies
     * @param JSON $json
     * @param string $preferencesExpiry
     */
    public function __construct(CookieHandler $cookies, Json $json, string $preferencesExpiry)
    {
        $this->cookies = $cookies;
        $this->json = $json;
        $this->preferencesExpiry = $preferencesExpiry;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $applicationID
     * @param string $environmentID
     * @param string|null $viewID
     *
     * @return ResponseInterface
     */
    public function save(
        ServerRequestInterface $request,
        ResponseInterface $response,
        $applicationID,
        $environmentID,
        $viewID
    ): ResponseInterface {
        // we store each app stickyness individually per app, but in the same cookie.
        $stickies = $this->unpackStickies($request);

        if ($viewID == null) {
            unset($stickies[$applicationID][$environmentID]);
        } else {
            $stickies[$applicationID][$environmentID] = $viewID;
        }

        return $this->cookies->withCookie(
            $response,
            self::COOKIE_NAME,
            $this->json->encode($stickies),
            $this->preferencesExpiry
        );
    }

    /**
     * Get the current view preference for an application.
     *
     * @param ServerRequestInterface $request
     * @param string $applicationID
     * @param string $environmentID
     *
     * @return string|null
     */
    public function get(ServerRequestInterface $request, $applicationID, $environmentID): ?string
    {
        $stickies = $this->unpackStickies($request);
        if (isset($stickies[$applicationID][$environmentID])) {
            return $stickies[$applicationID][$environmentID];
        }

        return null;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function unpackStickies(ServerRequestInterface $request): array
    {
        $stickies = $this->cookies->getCookie($request, self::COOKIE_NAME);

        // if the cookie is set
        if ($stickies !== null) {
            $stickies = $this->json->decode($stickies);
        }

        // decoded invalid, or not set
        if (!is_array($stickies)) {
            $stickies = [];
        }

        return $stickies;
    }
}
