<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Service;

use Exception;
use GuzzleHttp\BatchResults;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Pool;

trait ConsulBatchTrait
{
    /**
     * @param string $method
     * @param string|array $url
     * @param array $options
     *
     * @return RequestInterface
     */
    private function createRequest($method, $url, array $options = [])
    {
        $requestOptions = array_merge([
            'exceptions' => true
        ], $options);

        return $this->guzzle->createRequest($method, $url, $requestOptions);
    }

    /**
     * Requires Guzzle 5
     *
     * @param RequestInterface[] $requests
     *
     * @return null
     */
    private function handleBatch(array $requests)
    {
        $responses = Pool::batch($this->guzzle, $requests);

        return $this->handleResponses(array_keys($requests), $responses);
    }

    /**
     * Requires Guzzle 5
     *
     * Keys MUST have the same number of items as responses, as they are matched against the guzzle responses
     * so we know which response corresponds to which configuration property key.
     *
     * @param string[] $keys
     * @param BatchResults[] $responses
     *
     * @return array of statuses
     */
    private function handleResponses(array $keys, $responses)
    {
        $statuses = [];

        // @todo handle if count(keys) and count($responses) mismatch
        foreach ($responses as $response) {
            $key = array_shift($keys);

            $statuses[$key] = $this->parseBatchResponse($key, $response);
        }

        return $statuses;
    }

    /**
     * @param string $key
     * @param ResponseInterface|Exception $response
     *
     * @return string|bool|null
     *     true: PUT/DELETE successful
     *     false: PUT/DELETE failure - probably consul cas failure
     *     null: Something weird happened
     *     string: An error of some sort occurred, unexpected
     */
    private function parseBatchResponse($key, $response)
    {
        if ($response instanceof Exception) {
            return sprintf('An unexpected error occurred while updating "%s" : %d', $key, $response->getCode());
        }

        if ($response instanceof ResponseInterface) {
            $code = $response->getStatusCode();

            if ($code >= 200 && $code < 400) {
                $body = (string) $response->getBody();

                // Every SUCCESSFUL consul kv response MUST be "true" as a response.
                return ($body === 'true');

            } else {
                return sprintf('An unexpected error occurred while updating "%s" : %d', $key, $response->getStatusCode());
            }
        }

        return null;
    }
}
