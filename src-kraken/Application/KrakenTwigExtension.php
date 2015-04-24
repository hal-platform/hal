<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Application;

use QL\Kraken\Entity\Property;
use QL\Kraken\Entity\PropertySchema;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleTest;

class KrakenTwigExtension extends Twig_Extension
{
    const NAME = 'kraken';

    /**
     * Get the extension name
     *
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('formatSchemaType', [$this, 'formatPropertySchemaType']),
            new Twig_SimpleFilter('formatPropertyValue', [$this, 'formatPropertyValue'])
        ];
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getTests()
    {
        return [
            new Twig_SimpleTest('property', function ($entity) {
                return $entity instanceof Property;
            }),
            new Twig_SimpleTest('schema', function ($entity) {
                return $entity instanceof PropertySchema;
            })
        ];
    }

    /**
     * Format a property schema data type for display
     *
     * @param PropertySchema|string|null $schema
     *
     * @return string
     */
    public function formatPropertySchemaType($schema = null)
    {
        if ($schema instanceof PropertySchema) {
            $schema = $schema->dataType();
        }

        if ($schema) {
            $types = PropertySchema::$dataTypes;

            if (isset($types[$schema])) {
                return $types[$schema];
            }
        }

        return 'Unknown';
    }

    /**
     * Format a property value for display
     *
     * @param Property|null $schema
     * @param int $maxLength
     *
     * @return string|null
     */
    public function formatPropertyValue(Property $property = null, $maxLength = 100)
    {
        $maxLength = (int) $maxLength;

        if ($property === null) {
            return '';
        }

        if ($property->propertySchema()->isSecure()) {
            return null;
        }

        $value = json_decode($property->value());

        if (is_array($value)) {
            # W. T. F. - Handle "strings"

            if ($maxLength === 0) {
                $normalized = $value;
                goto SKIP_NORMALIZE;
            }

            $normalized = [];
            $total = 0;
            foreach ($value as $num => $item) {
                $total += mb_strlen($item);
                if ($total > $maxLength) {
                    $normalized[] = sprintf('...%d more items', count($value) - $num);
                    break;
                } else {
                    $normalized[] = $item;
                }
            }

            SKIP_NORMALIZE:
            return json_encode($normalized, JSON_PRETTY_PRINT);

        } elseif (is_string($value)) {
            if ($maxLength > 0) {
                $len = mb_strlen($value);
                if ($len > $maxLength) {
                    $value = substr($value, 0, $maxLength) . "...";
                }
            }

        } elseif (is_scalar($value)) {
            return var_export($value, true);
        }

        return $value;
    }
}
