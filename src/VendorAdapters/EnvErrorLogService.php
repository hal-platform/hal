<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\VendorAdapters;

use QL\MCP\Logger\Service\ErrorLogService;
use QL\MCP\Logger\Service\SerializerInterface;

class EnvErrorLogService extends ErrorLogService
{
    /**
     * @param SerializerInterface $serializer
     * @param array $configuration
     */
    public function __construct(SerializerInterface $serializer = null, array $configuration = [])
    {
        $types = ['OPERATING_SYSTEM', 'SAPI', 'FILE'];

        if (isset($configuration[static::CONFIG_TYPE]) && $type = $configuration[static::CONFIG_TYPE]) {
            if (in_array($type, $types)) {
                if (defined("static::${type}")) {
                    $configuration[static::CONFIG_TYPE] = constant("static::${type}");
                }
            }
        }

        parent::__construct($serializer, $configuration);
    }
}
