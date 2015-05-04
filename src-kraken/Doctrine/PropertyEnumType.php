<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Doctrine;

use Doctrine\DBAL\Types\Type as BaseType;
use QL\Hal\Core\Type\EnumTypeTrait;

/**
 * Property Type Enum
 */
class PropertyEnumType extends BaseType
{
    const TYPE_STRING = 'string';
    const TYPE_STRINGS = 'strings';
    const TYPE_FLAG = 'bool';
    const TYPE_INT = 'int';
    const TYPE_FLOAT = 'float';

    use EnumTypeTrait;

    /**
     * The enum data type
     */
    const TYPE = 'propertyenum';

    /**
     * The enum allowed values
     *
     * @return array
     */
    public static function values()
    {
        return [
            self::TYPE_STRING,
            self::TYPE_STRINGS,
            self::TYPE_FLAG,
            self::TYPE_INT,
            self::TYPE_FLOAT
        ];
    }

    /**
     * The enum allowed values
     *
     * @return array
     */
    public static function map()
    {
        return [
            self::TYPE_STRING => 'Text',
            self::TYPE_STRINGS => 'List (text)',
            self::TYPE_FLAG => 'Flag',
            self::TYPE_INT => 'Number (integer)',
            self::TYPE_FLOAT => 'Number (decimal)'
        ];
    }
}
