<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Configuration;
use QL\Kraken\Entity\Environment;
use QL\Kraken\Entity\Target;

class ConsulService
{
    const KV_ENDPOINT = '/{version}/kv/{application}/configuration';

    /**
     * @type Client
     */
    private $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param Configuration $configuration
     * @param Target $target
     *
     * @return bool
     */
    public function sendConfiguration(Configuration $configuration, Target $target)
    {
        $endpoint = $this->buildEndpoint($target->application(), $target->environment());
        if (!$endpoint) {
            return false;
        }

        $options = [
            'body' => $configuration->configuration()
        ];

        if ($token = $target->environment()->consulToken()) {
            $options['query'] = ['token' => $token];
        }

        try {
            $response = $this->client->put($endpoint, $options);

        } catch (RequestException $ex) {
            return false;
        }

        $body = (string) $response->getBody();
        if ($body === 'true') {
            return true;
        }

        return false;
    }

    /**
     * @param Application $application
     * @param Environment $environment
     *
     * @return array|null
     */
    private function buildEndpoint(Application $application, Environment $environment)
    {
        $applicationId = $application->id();
        $host = $environment->consulServer();

        if (!$applicationId || !$host) {
            return null;
        }

        return [
            rtrim($host, '/') . self::KV_ENDPOINT,
            [
                'version' => 'v1',
                'application' => $applicationId
            ]
        ];
    }
}
