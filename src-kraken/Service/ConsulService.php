<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Service;

use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ParseException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Utils;
use MCP\Cache\CachingTrait;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Environment;
use QL\Kraken\Entity\Target;
use QL\Kraken\Service\ConsulBatchTrait;

class ConsulService
{
    use CachingTrait;
    use ConsulBatchTrait;

    const KV_ENDPOINT = '/{version}/kv/{application}/';
    const CACHE_CHECKSUMS = 'consul:%s:checksums';
    const CACHE_CHECKSUMS_TTL = 'consul:%s:checksums';

    const ERR_TARGET_FAILURE = 'Update failed. Application target is misconfigured.';
    const ERR_CONSUL_CONNECTION_FAILURE = 'Update failed. Consul could not be contacted.';
    const ERR_BAD_CONSUL_RESPONSE = 'Update failed. Unexpected response from Consul.';

    /**
     * @type Client
     */
    private $guzzle;

    private $environentalize;

    /**
     * @param Client $guzzle
     */
    public function __construct(Client $guzzle)
    {
        $this->guzzle = $guzzle;
        $this->environentalize = true;
    }

    /**
     * Potential returns:
     *     $withData: false
     *         - '/path/key1'
     *         - '/path/key2'
     *         - '/path/key3'
     *
     *     $withData: true
     *         '/path/key1': ['modify' => 'ModifyIndex', 'value' => 'value1']
     *         '/path/key2': ['modify' => 'ModifyIndex', 'value' => 'value2']
     *         '/path/key3': ['modify' => 'ModifyIndex', 'value' => 'value3']
     *
     * @param Target $target
     * @param bool $withData
     *
     * @throws ConsulConnectionException
     *
     * @return string[]|null
     */
    public function getDeployedConfiguration(Target $target, $withData = false)
    {
        if (!$endpoint = $this->buildEndpoint($target->application(), $target->environment())) {
            throw new ConsulConnectionException(self::ERR_TARGET_FAILURE);
        }

        $query = ['recurse' => 1];

        if (!$withData) {
            $query['keys'] = 1;
        }

        if ($token = $target->environment()->consulToken()) {
            $query['token'] = $token;
        }

        try {
            $response = $this->guzzle->get($endpoint, ['query' => $query]);
            $json = $response->json();

        } catch (ParseException $ex) {
            throw new ConsulConnectionException(self::ERR_BAD_CONSUL_RESPONSE);

        } catch (RequestException $ex) {
            if ($ex->getCode() === 404) return [];
            throw new ConsulConnectionException(self::ERR_CONSUL_CONNECTION_FAILURE);
        }

        $keyPrefix = explode('/kv/', $endpoint);

        return $this->formatKeyResponses($json, array_pop($keyPrefix));
    }

    /**
     * Example return:
     *
     * @param Target $target
     * @param string[] $properties
     *
     * @throws ConsulConnectionException
     *
     * @return ConsulResponse[]
     *     array: A list of statuses. The update worked. Probably? MAY be empty!
     */
    public function syncConfiguration(Target $target, array $properties = [])
    {
        if (!$endpoint = $this->buildEndpoint($target->application(), $target->environment())) {
            throw new ConsulConnectionException(self::ERR_TARGET_FAILURE);
        }

        // Get what is currently deployed in consul kv
        $deployed = $this->getDeployedConfiguration($target, true);

        // cross reference new properties, to check which props need to be deleted
        $deletes = [];
        foreach ($deployed as $key => $data) {
            if (!isset($properties[$key])) {
                $deletes[$key] = $data;
            }
        }

        $query = [];
        if ($token = $target->environment()->consulToken()) {
            $query['token'] = $token;
        }

        $requests = [];

        // Add PUTs
        foreach ($properties as $key => $data) {
            $url = $endpoint . $key;
            $requestQuery = [];
            if (isset($deployed[$key])) {
                $requestQuery['cas'] = $deployed[$key]['modify'];
                // $requestQuery['cas'] = 'derp';
            }

            $requests[$key] = $this->createRequest('PUT', $url, [
                'body' => $data,
                'query' => array_merge($query, $requestQuery)
            ]);
        }

        // Add DELETEs
        foreach ($deletes as $key => $property) {
            $url = $endpoint . $key;
            $requestQuery = ['cas' => $property['modify']];

            $requests[$key] = $this->createRequest('DELETE', $url, ['query' => array_merge($query, $requestQuery)]);
        }

        // clear checksum cache
        $key = sprintf(self::CACHE_CHECKSUMS, $target->application()->id());
        $this->setToCache($key, null, 1);

        // This could take a while...
        $updates = $this->handleBatch($requests);

        array_walk($updates, function(&$v, $k) use ($deletes) {
            $r = new ConsulResponse($k, isset($deletes[$k]) ? 'delete' : 'update');
            $v = $r->withDetail($v);
        });

        return $updates;
    }

    /**
     * @param Target $target
     *
     * @return string[]
     */
    public function getChecksums(Target $target)
    {
        $key = sprintf(self::CACHE_CHECKSUMS, $target->application()->id());
        if (null !== ($data = $this->getFromCache($key))) {
            return json_decode($key);
        }

        try {
            $current = $this->getDeployedConfiguration($target, true);
        } catch (ConsulConnectionException $ex) {
            return [];
        }

        array_walk($current, function(&$v, $k) {
            $v = sha1($v['value']);
        });

        $this->setToCache($key, json_encode($current), self::CACHE_CHECKSUMS_TTL);
        return $current;
    }

    /**
     * @param Application $application
     * @param Environment $environment
     *
     * @return array|null
     */
    private function buildEndpoint(Application $application, Environment $environment)
    {
        $applicationId = $application->coreId();
        $host = $environment->consulServer();

        if (!$applicationId || !$host) {
            return null;
        }

        $endpoint = rtrim($host, '/') . self::KV_ENDPOINT;

        if ($this->environentalize) {
            $endpoint .= $environment->name() . '/';
        }

        // shitty. @todo replace with uri template
        return Utils::uriTemplate($endpoint, [
            'version' => 'v1',
            'application' => $applicationId
        ]);
    }

    /**
     * Potential returns:
     *         If a list of keys rovided
     *         - '/path/key1'
     *         - '/path/key2'
     *         - '/path/key3'
     *
     *          If a list of key objects is provided
     *         '/path/key1': ['modify' => 'ModifyIndex', 'value' => 'value1']
     *         '/path/key2': ['modify' => 'ModifyIndex', 'value' => 'value2']
     *         '/path/key3': ['modify' => 'ModifyIndex', 'value' => 'value3']
     *
     * @param Target $target
     * @param string $keyPrefix
     *
     * @return array
     */
    private function formatKeyResponses(array $payload, $keyPrefix = '')
    {
        $properties = [];

        foreach ($payload as $property) {

            $key = is_array($property) ? $property['Key'] : $property;

            if ($keyPrefix) {
                $key = str_replace($keyPrefix, '', $key);
            }

            if (is_array($property)) {
                $properties[$key] = [
                    'modify' => $property['ModifyIndex'],
                    'value' => base64_decode($property['Value'])
                ];
            } else {
                $properties[] = $key;
            }
        }

        return $properties;
    }
}
