<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Github;

use GuzzleHttp\Client as Guzzle;
use QL\Panthor\Utility\Json;

class OAuthHandler
{
    /**
     * @var Guzzle
     */
    private $guzzle;

    /**
     * @var string
     */
    private $ghBaseApiUrl;
    private $ghBaseUrl;
    private $ghClientId;
    private $ghClientSecret;

    /**
     * @var array
     */
    private static $requiredScopes = ['repo:status', 'repo_deployment'];
    /**
     * @var Json
     */
    private $json;

    /**
     * @param Guzzle $guzzle
     * @param string $ghBaseApiUrl
     * @param string $ghBaseUrl
     * @param string $ghClientId
     * @param string $ghClientSecret
     */
    public function __construct(Guzzle $guzzle, $ghBaseApiUrl, $ghBaseUrl, $ghClientId, $ghClientSecret, Json $json)
    {
        $this->guzzle = $guzzle;

        $this->ghBaseApiUrl = $ghBaseApiUrl;
        $this->ghBaseUrl = $ghBaseUrl;
        $this->ghClientId = $ghClientId;
        $this->ghClientSecret = $ghClientSecret;
        $this->json = $json;
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
            'form_params' => [
                'client_id' => $this->ghClientId,
                'client_secret' => $this->ghClientSecret,
                'code' => $code
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            return '';
        }

        $decoded = $this->json->decode($response->getBody()->getContents());
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
}
