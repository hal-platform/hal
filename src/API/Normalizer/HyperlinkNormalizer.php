<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Api\Normalizer;

use Hal\UI\Api\Hyperlink;
use Hal\UI\Api\NormalizerInterface;
use QL\Panthor\Utility\URI;

class HyperlinkNormalizer implements NormalizerInterface
{
    /**
     * @var URI
     */
    private $uri;

    /**
     * @param URI $uri
     */
    public function __construct(URI $uri)
    {
        $this->uri = $uri;
    }

    /**
     * @param Hyperlink $input
     *
     * @inheritDoc
     */
    public function normalize($input)
    {
        $normalized = $input->jsonSerialize();

        $href = $input->href();

        if (is_string($href)) {
            if (stripos($href, 'http') === 0) {
                return $normalized;
            } else {
                $href = [$href];
            }
        }

        // @todo change to absolute URL
        // $normalized['href'] = $this->uri->absoluteUrlFor($request->getUri(), ...$href);
        $normalized['href'] = $this->uri->uriFor(...$href);

        return $normalized;
    }
}
