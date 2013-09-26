<?php
/**
 * @copyright ©2013 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal;

use Exception;

class ConfigReader
{
    public function __construct(/* apc cache here for prod? */)
    {

    }

    public function load($filename)
    {
        $data = parse_ini_file($filename, true);
        if (false === $data) {
            throw new Exception('Parsing database config file failed. Please see conf.ini.dist if you are not sure what to put in conf.ini .');
        }

        list($db_dsn, $db_user, $db_pass, $build_user, $ssh_user, $gh_token, $gh_baseurl) = $this->importConfigValues($data);
        $this->validateConfigValues($db_dsn, $db_user, $db_pass, $build_user, $ssh_user, $gh_token, $gh_baseurl);

        return [
            'db_dsn' => $data['db']['dsn'],
            'db_user' => $data['db']['user'],
            'db_pass' => $data['db']['pass'],
            'build_user' => $data['push']['build_user'],
            'ssh_user' => $data['push']['ssh_user'],
            'github_token' => $data['github']['api_token'],
            'github_baseurl' => $data['github']['base_url'],
        ];
    }

    /**
     * @param $db_dsn
     * @param $db_user
     * @param $db_pass
     * @param $build_user
     * @param $ssh_user
     * @param $gh_token
     * @param $gh_baseurl
     * @throws \Exception
     */
    private function validateConfigValues($db_dsn, $db_user, $db_pass, $build_user, $ssh_user, $gh_token, $gh_baseurl)
    {
        if (
            is_null($db_dsn) ||
            is_null($db_user) ||
            is_null($db_pass) ||
            is_null($build_user) ||
            is_null($ssh_user) ||
            is_null($gh_token) ||
            is_null($gh_baseurl)
        ) {
            throw new Exception('Parsing database config file failed. Please see conf.ini.dist if you are not sure what to put in conf.ini .');
        }
    }

    /**
     * @param $data
     * @return array
     */
    private function importConfigValues($data)
    {
        $db_dsn = isset($data['db']['dsn']) ? $data['db']['dsn'] : null;
        $db_user = isset($data['db']['user']) ? $data['db']['user'] : null;
        $db_pass = isset($data['db']['pass']) ? $data['db']['pass'] : null;
        $build_user = isset($data['push']['build_user']) ? $data['push']['build_user'] : null;
        $ssh_user = isset($data['push']['ssh_user']) ? $data['push']['ssh_user'] : null;
        $gh_token = isset($data['github']['api_token']) ? $data['github']['api_token'] : null;
        $gh_baseurl = isset($data['github']['base_url']) ? $data['github']['base_url'] : null;

        return array($db_dsn, $db_user, $db_pass, $build_user, $ssh_user, $gh_token, $gh_baseurl);
    }
}
