<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Utility;

use Exception;
use GuzzleHttp\ClientInterface as Guzzle;
use MCP\Crypto\Package\QuickenMessagePackage;
use MCP\Crypto\Primitive\Factory as PrimitiveFactory;
use MCP\DataType\HttpUrl;
use QL\MCP\QKS\Crypto\Client\HttpClient as QKSClient;
use QL\MCP\QKS\Crypto\Client\Parser\JsonParser;
use QL\MCP\QKS\Crypto\Envelope\Box\Factory as BoxFactory;
use QL\MCP\QKS\Crypto\Envelope\Factory as EnvelopeFactory;
use QL\MCP\Http\Client as HttpClient;

class QKSFactory
{
    /**
     * @type HttpClient
     */
    private $http;

    /**
     * @type JsonParser
     */
    private $parser;

    /**
     * @type EnvelopeFactory
     */
    private $envelopeFactory;

    /**
     * @type BoxFactory
     */
    private $boxFactory;

    /**
     * @param HttpClient $guzzle
     * @param JsonParser $parser
     * @param EnvelopeFactory $envelopeFactory
     * @param BoxFactory $boxFactory
     */
    public function __construct(
        HttpClient $http,
        JsonParser $parser,
        EnvelopeFactory $envelopeFactory,
        BoxFactory $boxFactory
    ) {
        $this->http = $http;
        $this->parser = $parser;

        $this->envelopeFactory = $envelopeFactory;
        $this->boxFactory = $boxFactory;
    }

    /**
     * @param string $url
     * @param string $clientID
     * @param string $clientSecret
     * @param string $sendingKey
     *
     * @return QuickenMessagePackage|null
     */
    public function getQMP($url, $clientID, $clientSecret, $sendingKey)
    {
        if (!$url = HttpUrl::create($url)) {
            return null;
        }

        try {
            $service = $this->getQKSClient($url, $clientID, $clientSecret);
        } catch (Exception $ex) {
            return null;
        }

        return new QuickenMessagePackage(new PrimitiveFactory, $this->boxFactory, $service, $sendingKey);
    }

    /**
     * @param HttpUrl $url
     * @param string $clientID
     * @param string $clientSecret
     *
     * @return QKSClient
     */
    public function getQKSClient(HttpUrl $url, $clientID, $clientSecret)
    {
        $url = sprintf('%s/{+resource}', trim($url->asString(), '/'));

        return new QKSClient($this->http, $this->parser, $this->envelopeFactory, [
            QKSClient::CONFIG_CLIENT_ID => $clientID,
            QKSClient::CONFIG_CLIENT_SECRET => $clientSecret,
            QKSClient::CONFIG_HOSTNAME => 'example.com', //  because its required
            QKSClient::CONFIG_TEMPLATE => $url
        ]);
    }
}
