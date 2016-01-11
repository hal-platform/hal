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
use MCP\Crypto\Package\QuickenMessagePackage\Header\MetaSerializer;
use MCP\Crypto\Primitive\Factory as PrimitiveFactory;
use QL\MCP\QKS\Crypto\Client\GuzzleClient;
use QL\MCP\QKS\Crypto\Client\Parser\JsonParser;
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
     * @type MetaSerializer
     */
    private $serializer;

    /**
     * @param Guzzle $guzzle
     * @param JsonParser $parser
     * @param EnvelopeFactory $envelopeFactory
     * @param MetaSerializer $serializer
     */
    public function __construct(
        Guzzle $guzzle,
        JsonParser $parser,
        EnvelopeFactory $envelopeFactory,
        MetaSerializer $serializer
    ) {
        $this->guzzle = $guzzle;
        $this->parser = $parser;

        $this->envelopeFactory = $envelopeFactory;
        $this->serializer = $serializer;
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
        if (!$url = $this->validateUrl($url)) {
            return null;
        }

        try {
            $service = $this->getQKSClient($url, $clientID, $clientSecret);
        } catch (Exception $ex) {
            return null;
        }

        return new QuickenMessagePackage(new PrimitiveFactory, $this->serializer, $service, $sendingKey);
    }

    /**
     * @param string $url
     * @param string $clientID
     * @param string $clientSecret
     *
     * @return QKSClient
     */
    private function getQKSClient($url, $clientID, $clientSecret)
    {
        $baseURL = trim($url, '/');

        return new GuzzleClient($this->guzzle, $this->parser, $this->envelopeFactory, [
            'client_id' => $clientID,
            'client_secret' => $clientSecret,
            'seal_url' => new UriTemplate(sprintf('%s/crypto/seal', $baseURL)),
            'open_url' => new UriTemplate(sprintf('%s/crypto/open', $baseURL))
        ]);
    }

    /**
     * @param string $url
     *
     * @return string|null
     */
    private function validateUrl($url)
    {
        if (strlen($url) > 200) {
            return null;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $url;
    }
}
