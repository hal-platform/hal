<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Service;

use Hal\Core\Entity\Application;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\HTTP\CookieHandler;
use QL\Panthor\Utility\JSON;

class StickyEnvironmentService
{
    const COOKIE_NAME = 'stickyenvironment';

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
    public function __construct(CookieHandler $cookies, JSON $json, string $preferencesExpiry)
    {
        $this->cookies = $cookies;
        $this->json = $json;
        $this->preferencesExpiry = $preferencesExpiry;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param Application|string $applicationID
     * @param string $environmentID
     *
     * @return ResponseInterface
     */
    public function save(
        ServerRequestInterface $request,
        ResponseInterface $response,
        $applicationID,
        $environmentID
    ): ResponseInterface {
        if ($applicationID instanceof Application) {
            $applicationID = $applicationID->id();
        }

        // we store each repo stickyness individually per repo, but in the same cookie.
        $stickies = $this->unpackStickies($request);
        $stickies[$applicationID] = $environmentID;

        return $this->cookies->withCookie(
            $response,
            self::COOKIE_NAME,
            $this->json->encode($stickies),
            $this->preferencesExpiry
        );
    }

    /**
     * Get the current env preference for an application.
     *
     * @param ServerRequestInterface $request
     * @param Application|string $applicationID
     *
     * @return string|null
     */
    public function get(ServerRequestInterface $request, $applicationID): ?string
    {
        if ($applicationID instanceof Application) {
            $applicationID = $applicationID->id();
        }

        $stickies = $this->unpackStickies($request);

        if (isset($stickies[$applicationID])) {
            return $stickies[$applicationID];
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
