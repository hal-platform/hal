<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\API;

use InvalidArgumentException;

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
     * Normalize all known object types.
     *
     * @inheritDoc
     */
    public function normalize($input)
    {
        if ($input === null) {
            return null;
        }

        if (is_array($input)) {
            return array_map(function($item) {
                return $this->normalize($item);
            }, $input);
        }

        if ($normalizer = $this->findNormalizer($input)) {
            $normalized = $normalizer->normalize($input);

            // Run it through the base normalizer again
            // @todo WHY
            return $this->normalize($normalized);
        }

        // Allow other types to pass through
        return $input;
    }

    /**
     * Create a link for the input resource.
     *
     * @inheritDoc
     */
    public function link($input): ?Hyperlink
    {
        if ($input === null) {
            return null;
        }

        if ($normalizer = $this->findNormalizer($input)) {
            return $normalizer->link($input);
        }

        return null;
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

    /**
     * @param mixed $input
     *
     * @return NormalizerInterface|null
     */
    private function findNormalizer($input)
    {
        $fqcn = $this->resolveType($input);

        foreach ($this->normalizers as $type => $normalizer) {
            if ($input instanceof $type || $fqcn === $type) {
                return $normalizer;
            }
        }
    }

    /**
     * @param mixed
     *
     * @return string
     */
    private function resolveType($input)
    {
        $fqcn = gettype($input);
        if ($fqcn === 'object') {
            $fqcn = get_class($input);
        }

        return $fqcn;
    }
}
