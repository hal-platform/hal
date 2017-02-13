<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Api\Normalizer;

use Hal\UI\Api\Hyperlink;
use Hal\UI\Api\NormalizerInterface;
use QL\Panthor\Utility\Url;

class HyperlinkNormalizer implements NormalizerInterface
{
    /**
     * @var Url
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
