<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Api\Normalizer;

use Hal\UI\Api\Hyperlink;
use Hal\UI\Api\HypermediaResource;
use Hal\UI\Api\ResourceNormalizerInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\Utility\URI;

class HyperlinkNormalizer implements ResourceNormalizerInterface
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
    public function normalize($link)
    {
        if (!$link instanceof Hyperlink) {
            return null;
        }

        $normalized = $link->jsonSerialize();
        $normalized['href'] = $this->resolveURL($link->href());
        return $normalized;
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
     * @return HypermediaResource|null
     */
    public function resource($link, array $embed = []): ?HypermediaResource
    {
        return null;
    }

    /**
     * @param array|string $href
     *
     * @return string
     */
    private function resolveURL($href)
    {
        if (is_string($href)) {
            if (stripos($href, 'http') === 0) {
                return $href;
            } else {
                $href = [$href];
            }
        }

        // @todo THIS SUCKS! Better way to get at runtime?
        return $this->uri->absoluteURIFor($this->baseRequest->getUri(), ...$href);
    }
}
