<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Application;

use MCP\DataType\Time\Clock;
use QL\Kraken\Core\Entity\Application;
use QL\Kraken\Core\Entity\Schema;
use QL\Hal\Core\Entity\User;

class SkeletorConfigurationTemplate
{
    /**
     * @type clock
     */
    private $clock;

    /**
     * @type callable
     */
    private $random;

    /**
     * @param Clock $clock
     * @param callable $random
     */
    public function __construct(Clock $clock, callable $random)
    {
        $this->clock = $clock;
        $this->random = $random;
    }

    /**
     * @param Application $application
     * @param User $user
     *
     * @return Schema[]
     */
    public function generate(Application $application, User $user)
    {
        $now = $this->clock->read();
        $template = $this->template();

        $schema = [];
        foreach ($template as $prop) {
            $description = array_key_exists('description', $prop) ? $prop['description'] : '';
            $isSecure = array_key_exists('isSecure', $prop) ? $prop['isSecure'] : false;
            $id = call_user_func($this->random);

            $schema[] = (new Schema)
                ->withId($id)
                ->withKey($prop['key'])
                ->withDataType($prop['dataType'])
                ->withDescription($description)
                ->withIsSecure($isSecure)
                ->withCreated($now)
                ->withApplication($application)
                ->withUser($user);
        }

        return $schema;
    }

    /**
     * @return string
     */
    private function template()
    {
        return [

            // Database
            [
                'key' => 'database.eternia.dsn',
                'dataType' => 'string',
                'description' => 'Eternia database PDO DSN (MySQL)'
            ],
            [
                'key' => 'database.eternia.username',
                'dataType' => 'string',
                'description' => 'Eternia database username (MySQL)'
            ],
            [
                'key' => 'database.eternia.password',
                'dataType' => 'string',
                'description' => 'Eternia database password (MySQL)',
                'isSecure' => true
            ],

            // Eternia
            [
                'key' => 'eternia.enabled',
                'dataType' => 'bool',
                'description' => 'Is Eternia enabled?'
            ],
            [
                'key' => 'eternia.cache_enabled',
                'dataType' => 'bool',
                'description' => 'Is Eternia caching enabled?'
            ],
            [
                'key' => 'eternia.cache_ttl',
                'dataType' => 'int',
                'description' => 'Time in seconds to cache Eternia data'
            ],
            [
                'key' => 'eternia.redis_servers',
                'dataType' => 'strings',
                'description' => 'Redis servers for Eternia previews (requires tcp:// scheme)'
            ],

            // Metrics
            [
                'key' => 'metrics.enabled',
                'dataType' => 'bool',
                'description' => 'Is metrics enabled?'
            ],

            // skeletor
            [
                'key' => 'skeletor.session.redis_servers',
                'dataType' => 'strings',
                'description' => 'Redis servers for session storage (requires tcp:// scheme)'
            ],
            [
                'key' => 'skeletor.memcached.servers',
                'dataType' => 'strings',
                'description' => 'Memcached servers'
            ]
        ];
    }

}
