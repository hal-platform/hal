<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\GithubApi;

use Github\HttpClient\CachedHttpClient;

/**
 * Extending the knplabs api to not be completely horrible and stupid.
 *
 * @internal
 */
class HackCachedHttpClient extends CachedHttpClient
{
    /**
     * {@inheritdoc}
     */
    public function request($path, $body = null, $httpMethod = 'GET', array $headers = array(), array $options = array())
    {
        $cacheKey = $path;
        if (isset($options['query'])) {
            $cacheKey .= serialize($options['query']);
        }

        $response = parent::request($path, $body, $httpMethod, $headers, $options);

        if (304 == $response->getStatusCode()) {
            return $this->getCache()->get($cacheKey);
        }

        $this->getCache()->set($cacheKey, $response);

        return $response;
    }

    /**
     * Create requests with If-Modified-Since headers
     *
     * {@inheritdoc}
     */
    protected function createRequest($httpMethod, $path, $body = null, array $headers = array(), array $options = array())
    {
        $cacheKey = $path;
        if (isset($options['query'])) {
            $cacheKey .= serialize($options['query']);
        }

        $request = parent::createRequest($httpMethod, $path, $body, $headers = array(), $options);

        if ($modifiedAt = $this->getCache()->getModifiedSince($cacheKey)) {
            $modifiedAt = new \DateTime('@'.$modifiedAt);
            $modifiedAt->setTimezone(new \DateTimeZone('GMT'));

            $request->addHeader(
                'If-Modified-Since',
                sprintf('%s GMT', $modifiedAt->format('l, d-M-y H:i:s'))
            );
        }
        if ($etag = $this->getCache()->getETag($cacheKey)) {
            $request->addHeader(
                'If-None-Match',
                $etag
            );
        }

        return $request;
    }
}
