<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use QL\Panthor\Bootstrap\Di as PanthorDi;

/**
 * Custom Di class to set the configuration path where HAL wants it.
 */
class Di extends PanthorDi
{
    const PRIMARY_CONFIGURATION_FILE = 'app/config.yml';
}
