<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Application;

use QL\Kraken\Entity\PropertySchema;
use Twig_Extension;
use Twig_SimpleFilter;

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
    public function getFunctions()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('formatSchemaType', [$this, 'formatPropertySchemaType']),
        ];
    }

    /**
     * Format a property schema data type for display
     *
     * @param PropertySchema|null $schema
     *
     * @return string
     */
    public function formatPropertySchemaType(PropertySchema $schema = null)
    {
        if ($schema) {
            $type = $schema->dataType();

            $types = PropertySchema::$dataTypes;

            if (isset($types[$type])) {
                return $types[$type];
            }
        }

        return 'Unknown';
    }
}
