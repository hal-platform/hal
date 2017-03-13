<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Api\Normalizer;

use Hal\UI\Api\Hyperlink;
use Hal\UI\Api\NormalizerInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\Utility\URI;

class HyperlinkNormalizer implements NormalizerInterface
{
    /**
     * @var URI
     */
    private $uri;

    /**
     * @var ServerRequestInterface
     */
    private $baseRequest;

    /**
     * @param URI $uri
     * @param ServerRequestInterface $baseRequest
     */
    public function __construct(URI $uri, ServerRequestInterface $baseRequest)
    {
        $this->uri = $uri;
        $this->baseRequest = $baseRequest;
    }

    /**
     * @param Hyperlink $input
     *
     * @return array|null
     */
    public function normalize($input)
    {
        return $this->resource($input);
    }

    /**
     * @param Group|null $link
     *
     * @return Hyperlink|null
     */
    public function link($link): ?Hyperlink
    {
        return null;
    }

    /**
     * @param Hyperlink|null $link
     *
     * @return array|null
     */
    public function resource($link)
    {
        if (!$link instanceof Hyperlink) {
            return null;
        }

        $normalized = $link->jsonSerialize();

        $href = $link->href();

        if (is_string($href)) {
            if (stripos($href, 'http') === 0) {
                return $normalized;
            } else {
                $href = [$href];
            }
        }

        // @todo THIS SUCKS! How to get at runtime?
        $normalized['href'] = $this->uri->absoluteURIFor($this->baseRequest->getUri(), ...$href);

        return $normalized;
    }
}
