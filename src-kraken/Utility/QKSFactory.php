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
use QL\MCP\QKS\Crypto\Client\GuzzleClient;
use QL\MCP\QKS\Crypto\Client\Parser\JsonParser;
use QL\MCP\QKS\Crypto\Envelope\Box\Factory as BoxFactory;
use QL\MCP\QKS\Crypto\Envelope\Factory as EnvelopeFactory;
use QL\UriTemplate\UriTemplate;

class QKSFactory
{
    /**
     * @type Guzzle
     */
    private $guzzle;

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
     * @param Guzzle $guzzle
     * @param JsonParser $parser
     * @param EnvelopeFactory $envelopeFactory
     * @param BoxFactory $boxFactory
     */
    public function __construct(
        Guzzle $guzzle,
        JsonParser $parser,
        EnvelopeFactory $envelopeFactory,
        BoxFactory $boxFactory
    ) {
        $this->guzzle = $guzzle;
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
        $baseURL = trim($url->asString(), '/');

        return new GuzzleClient($this->guzzle, $this->parser, $this->envelopeFactory, [
            'client_id' => $clientID,
            'client_secret' => $clientSecret,
            'seal_url' => new UriTemplate(sprintf('%s/crypto/seal', $baseURL)),
            'open_url' => new UriTemplate(sprintf('%s/crypto/open', $baseURL))
        ]);
    }
}
