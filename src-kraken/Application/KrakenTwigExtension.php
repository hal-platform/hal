<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Application;

use QL\Kraken\Diff;
use QL\Kraken\Entity\ConfigurationProperty;
use QL\Kraken\Entity\Property;
use QL\Kraken\Entity\Schema;
use QL\Kraken\Entity\Target;
use QL\Kraken\Doctrine\PropertyEnumType;
use QL\Panthor\Utility\Json;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleTest;

class KrakenTwigExtension extends Twig_Extension
{
    const NAME = 'kraken';
    const INVALID_DECODED_PROPERTY = 0xA9E1B2E76;

    /**
     * @type Json
     */
    private $json;

    /**
     * @param Json $json
     */
    public function __construct(Json $json)
    {
        $this->json = $json;
    }

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
            new Twig_SimpleFilter('formatSchemaType', [$this, 'formatSchemaType']),
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
                return $entity instanceof Schema;
            }),
            new Twig_SimpleTest('target', function ($entity) {
                return $entity instanceof Target;
            }),
            new Twig_SimpleTest('configurationProperty', function ($entity) {
                return $entity instanceof ConfigurationProperty;
            }),
            new Twig_SimpleTest('diff', function ($entity) {
                return $entity instanceof Diff;
            }),
            new Twig_SimpleTest('invalidProperty', function ($value) {
                return $value === self::INVALID_DECODED_PROPERTY;
            })
        ];
    }

    /**
     * Format a property schema data type for display
     *
     * @param SchemaConfigurationProperty|Diff||string|null $schema
     *
     * @return string
     */
    public function formatSchemaType($schema = null)
    {
        if ($schema instanceof Diff) {
            $schema = $schema->schema();
        }

        if ($schema instanceof Schema || $schema instanceof ConfigurationProperty) {
            $schema = $schema->dataType();
        } elseif (!is_string($schema)) {
            $schema = '???';
        }

        if ($schema) {
            $types = PropertyEnumType::map();

            if (isset($types[$schema])) {
                return $types[$schema];
            }
        }

        return 'Unknown';
    }

    /**
     * Format a property value for display
     *
     * @param ConfigurationProperty|Property|Diff|null $schema
     * @param int $maxLength
     *
     * @return string|null
     */
    public function formatPropertyValue($property, $maxLength = 100)
    {
        $maxLength = (int) $maxLength;

        if ($property instanceof Diff) {
            $property = $property->property();
        }

        if (!$property instanceof Property && !$property instanceof ConfigurationProperty) {
            return '';
        }

        if ($property instanceof Property && $property->schema()->isSecure()) {
            return null;
        }

        if ($property instanceof ConfigurationProperty && $property->isSecure()) {
            return null;
        }

        $value = $this->json->decode($property->value());
        if ($value === null) {
            return self::INVALID_DECODED_PROPERTY;
        }

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
            return $normalized;

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
