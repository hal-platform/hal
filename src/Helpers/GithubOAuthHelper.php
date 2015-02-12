<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Helpers;

use GuzzleHttp\Client as Guzzle;

class GithubOAuthHelper
{
    /**
     * @type Guzzle
     */
    private $guzzle;

    /**
     * @type string
     */
    private $ghBaseApiUrl;
    private $ghBaseUrl;
    private $ghClientId;
    private $ghClientSecret;

    /**
     * @type array
     */
    private static $requiredScopes = ['repo:status', 'repo_deployment'];

    /**
     * @param Guzzle $guzzle
     * @param string $ghBaseApiUrl
     * @param string $ghBaseUrl
     * @param string $ghClientId
     * @param string $ghClientSecret
     */
    public function __construct(Guzzle $guzzle, $ghBaseApiUrl, $ghBaseUrl, $ghClientId, $ghClientSecret)
    {
        $this->guzzle = $guzzle;

        $this->ghBaseApiUrl = $ghBaseApiUrl;
        $this->ghBaseUrl = $ghBaseUrl;
        $this->ghClientId = $ghClientId;
        $this->ghClientSecret = $ghClientSecret;
    }

    /**
     * @param string $code
     *
     * @return string
     */
    public function getOAuthAccessToken($code)
    {
        $url = rtrim($this->ghBaseUrl, '/') . '/login/oauth/access_token';

        $response = $this->guzzle->post($url, [
            'exceptions' => false,
            'headers' => ['Accept' => 'application/json'],
            'body' => [
                'client_id' => $this->ghClientId,
                'client_secret' => $this->ghClientSecret,
                'code' => $code
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            return '';
        }

        $decoded = $response->json();
        if (!isset($decoded['access_token']) || !$decoded['access_token']) {
            return '';
        }

        return $decoded['access_token'];
    }

    /**
     * @param string $state
     *
     * @return array
     */
    public function buildOAuthAuthorizationUrl($state)
    {
        $url = rtrim($this->ghBaseUrl, '/') . '/login/oauth/authorize';

        $query = [
            'client_id' => $this->ghClientId,
            'scope' => implode(',', static::$requiredScopes),
            'state' => $state
        ];

        return [$url, $query];
    }

    /**
     * @param string $token
     *
     * @return void
     */
    public function revokeToken($token)
    {
        if (!$token) {
            return;
        }

        $path = sprintf('/applications/%s/tokens/%s', $this->ghClientId, $token);
        $url = rtrim($this->ghBaseApiUrl, '/') . $path;

        $response = $this->guzzle->delete($url, [
            'exceptions' => false,
            'auth' => [$this->ghClientId, $this->ghClientSecret]
        ]);
    }

    /**
     * @param string $token
     *
     * @return void
     */
    public function asdasd($token)
    {
        if (!$token) {
            return;
        }
    }
}
