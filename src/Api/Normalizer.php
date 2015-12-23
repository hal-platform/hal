<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api;

use InvalidArgumentException;

/**
 * Base Normalizer
 */
class Normalizer implements NormalizerInterface
{
    /**
     * A map of types to normalizers
     *
     * @var array
     */
    private $normalizers;

    public function __construct()
    {
        $this->normalizers = [];
    }

    /**
     * Normalize all known object types
     *
     * {@inheritdoc}
     */
    public function normalize($input)
    {
        if (is_array($input)) {
            return array_map(function($item) {
                return $this->normalize($item);
            }, $input);
        }

        if ($input === null) {
            return null;
        }

        $fqcn = gettype($input);
        if ($fqcn === 'object') {
            $fqcn = get_class($input);
        }

        foreach ($this->normalizers as $type => $normalizer) {
            if ($input instanceof $type || $fqcn === $type) {
                $normalized = $normalizer->normalize($input);

                // Run it through the base normalizer again
                return $this->normalize($normalized);
            }
        }

        // Allow other types to pass through
        return $input;
    }

    /**
     * Add a normalizer to handle a type.
     *
     * Type MUST be a fully qualified class name.
     *
     * @param string $type
     * @param NormalizerInterface $normalizer
     *
     * @return void
     */
    public function addNormalizer($type, NormalizerInterface $normalizer)
    {
        $this->normalizers[$type] = $normalizer;
    }

    /**
     * Recursively resolve any objects in the tree of normalized values
     *
     * @param array $tree
     * @return array
     */
    public function resolve(array $tree)
    {
        array_walk_recursive($tree, function (&$leaf) {
            if (is_object($leaf)) {
                $leaf = $this->normalize($leaf);
            }
        });

        return $tree;
    }
}
