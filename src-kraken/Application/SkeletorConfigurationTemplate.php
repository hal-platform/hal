<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Application;

use MCP\DataType\Time\Clock;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Schema;
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

            // Eternia
            [
                'key' => 'eternia.cache',
                'dataType' => 'bool',
                'description' => 'Cache eternia data between requests?'
            ],
            [
                'key' => 'eternia.cache_ttl',
                'dataType' => 'int',
                'description' => 'Time in seconds to cache Eternia data'
            ],
            [
                'key' => 'eternia.redis_servers',
                'dataType' => 'strings',
                'description' => 'Redis servers for Eternia previews'
            ],

            // Logging
            [
                'key' => 'log.core_endpoint',
                'dataType' => 'string',
                'description' => 'Full URL to Sonic endpoint for core logger (Only affects Core logger handler)'
            ],
            [
                'key' => 'log.email',
                'dataType' => 'string',
                'description' => 'Send logs to this e-mail address (Only affects e-mail handler)'
            ],
            [
                'key' => 'log.handlers',
                'dataType' => 'strings',
                'description' => 'Fully qualified class names of log handlers'
            ],
            [
                'key' => 'log.minimum_level',
                'dataType' => 'string',
                'description' => 'Minimum level to log a message'
            ],
            [
                'key' => 'log.sensitive_keys',
                'dataType' => 'strings',
                'description' => 'POST and GET keys to be excluded from log context data'
            ],

            // skeletor
            [
                'key' => 'skeletor.cookie.encryption_key',
                'dataType' => 'string',
                'description' => 'Encryption key for cookies'
            ],
            [
                'key' => 'skeletor.cookie.unencrypted',
                'dataType' => 'strings',
                'description' => 'Unencrypted cookie names'
            ],
            [
                'key' => 'skeletor.error.controller',
                'dataType' => 'string',
                'description' => 'Fully qualified class name of the error controller'
            ],
            [
                'key' => 'skeletor.memcached.disable',
                'dataType' => 'bool',
                'description' => 'Disable memcache caching?'
            ],
            [
                'key' => 'skeletor.memcached.log_errors',
                'dataType' => 'bool',
                'description' => 'Log an error when a memcached server fails to connect?'
            ],
            [
                'key' => 'skeletor.memcached.servers',
                'dataType' => 'strings',
                'description' => 'Memcached servers'
            ],
            [
                'key' => 'skeletor.session.handler',
                'dataType' => 'string',
                'description' => 'Fully qualified class name of the session handler'
            ],
            [
                'key' => 'skeletor.session.redis_servers',
                'dataType' => 'strings',
                'description' => 'Redis servers for session storage'
            ],
            [
                'key' => 'skeletor.site.code',
                'dataType' => 'string',
                'description' => '2 character site-code, used by Eternia and Metrics'
            ],
            [
                'key' => 'skeletor.site.core_id',
                'dataType' => 'string',
                'description' => 'Core Application ID'
            ],
            [
                'key' => 'skeletor.site.name',
                'dataType' => 'string',
                'description' => 'The name of the application. Displayed to clients and used in the page title.'
            ],

            // skeletor - database
            [
                'key' => 'skeletor.database_persistence.eternia',
                'dataType' => 'bool',
                'description' => 'Enable connection persistence for this database'
            ],
            [
                'key' => 'skeletor.database.eternia',
                'dataType' => 'string',
                'description' => 'DSN of Eternia database',
                'isSecure' => true
            ],

            // Metrics
            [
                'key' => 'metrics.encryption_key',
                'dataType' => 'string',
                'description' => 'Encryption key for metrics',
                'isSecure' => true
            ],
            [
                'key' => 'skeletor.metrics.endpoint',
                'dataType' => 'string',
                'description' => 'Full URL of Sonic endpoint for metrics'
            ]

        ];
    }

}
