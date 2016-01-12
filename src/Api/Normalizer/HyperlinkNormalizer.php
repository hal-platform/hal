<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Api\Normalizer;

use QL\Panthor\Utility\Url;
use QL\Hal\Api\Hyperlink;
use QL\Hal\Api\NormalizerInterface;

class HyperlinkNormalizer implements NormalizerInterface
{
    /**
     * @type Url
     */
    private $url;

    /**
     * @param Url $url
     */
    public function __construct(Url $url)
    {
        $this->url = $url;
    }

    /**
     * @param Hyperlink $input
     *
     * {@inheritdoc}
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

        $normalized['href'] = call_user_func_array([$this->url, 'absoluteUrlFor'], $href);

        return $normalized;
    }
}
