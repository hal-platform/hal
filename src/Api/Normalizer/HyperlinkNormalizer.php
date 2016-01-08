<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
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
