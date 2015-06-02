<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Services;

use Aws\Common\Exception\AwsExceptionInterface;
use Aws\ElasticBeanstalk\ElasticBeanstalkClient;
use MCP\Cache\CachingTrait;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Services\ElasticBeanstalk\Environment;

/**
 * Retrieve data about elastic beanstalk
 *
 * Beware, this is a bit rough. Don't judge me.
 */
class ElasticBeanstalkService
{
    use CachingTrait;

    /**
     * @type ElasticBeanstalkClient
     */
    private $eb;

    /**
     * Used for sanity checking
     *
     * @type string
     */
    private $awsKey;
    private $awsSecret;

    /**
     * @param ElasticBeanstalkClient $eb
     * @param string $awsKey
     * @param string $awsSecret
     */
    public function __construct(ElasticBeanstalkClient $eb, $awsKey, $awsSecret)
    {
        $this->eb = $eb;
        $this->awsKey = $awsKey;
        $this->awsSecret = $awsSecret;
    }

    /**
     * Get an elastic beanstalk environment based on an HAL deployment.
     *
     * All deployments must be from the same EB application!
     *
     * Note that this method is a bit complex because EB env are
     * cached individually, but we batch the service call when possible.
     *
     * @param Deployment|Deployment[] $deployments
     *
     * @return Environment[]
     */
    public function getEnvironmentsByDeployments($deployments)
    {
        if (!is_array($deployments)) {
            $deployments = [$deployments];
        }

        // blow up if aws not authenticated
        if (!$this->awsKey || !$this->awsSecret) {
            return [];
        }

        // sanitize
        // create EBid => Deployment mapping
        $mapped = [];
        foreach ($deployments as $deployment) {
            if (!$deployment instanceof Deployment) {
                continue;
            }

            if (!$envId = $deployment->ebEnvironment()) {
                continue;
            }

            $mapped[$envId] = $deployment;
        }

        if (count($mapped) === 0) {
            return [];
        }

        // group deployments by EB application name

        $grouped = $this->groupByApplication($deployments);
        // Get EB App Name from the first deployment
        // $firstDeploy = reset($mapped);
        // $ebApplicationName = $firstDeploy->getRepository()->getEbName();

        $loaded = [];

        // Check cache, remove from grouped deployments if found
        foreach ($grouped as $appName => $appDeployments) {
            foreach ($appDeployments as $deployment) {
                $ebEnv = $deployment->ebEnvironment();

                $key = sprintf('aws.eb.env.%s', $ebEnv);
                $cached = $this->getFromCache($key);
                if ($cached instanceof Environment) {

                    $loaded[$deployment->id()] = $cached;
                    unset($grouped[$appName][$ebEnv]);
                    if (count($grouped[$appName]) === 0) {
                        unset($grouped[$appName]);
                    }
                }
            }
        }

        // All env were cached, no need to call service
        if (count($grouped) === 0) {
            return $loaded;
        }

        // Iterate through each application bucket and get all statuses for all envs in an application at a time
        foreach ($grouped as $ebApplicationName => $appDeployments) {

            // Call service
            $response = $this->callService('describeEnvironments', [
                'ApplicationName' => $ebApplicationName,
                'EnvironmentIds' => array_keys($appDeployments)
            ]);

            // Error state
            if (empty($response) || !isset($response['Environments'])) {
                continue;
            }

            // Add eb environments from aws response to $loaded
            foreach ($response['Environments'] as $environment) {
                $ebEnv = $this->buildEBEnvironment($environment);
                $key = sprintf('aws.eb.env.%s', $ebEnv->id());
                $this->setToCache($key, $ebEnv);

                if (isset($appDeployments[$ebEnv->id()])) {
                    $deployId = $appDeployments[$ebEnv->id()]->id();
                    $loaded[$deployId] = $ebEnv;
                    unset($appDeployments[$ebEnv->id()]);
                }
            }

            // Record was not returned in aws response, so add unknown
            if (count($appDeployments) > 0) {
                foreach ($appDeployments as $deployment) {
                    $loaded[$deployment->id()] = $this->buildEBEnvironment([]);
                }
            }
        }

        return $loaded;
    }

    /**
     * Group deployments by application
     *
     * @param Deployment[] $deployments
     *
     * @return array
     */
    private function groupByApplication(array $deployments)
    {
        // group by application name
        // each call to EB API is per eb-application
        $apps = [];
        foreach ($deployments as $dep) {
            $dep = $dep;

            if (!$dep instanceof Deployment) {
                continue;
            }

            $ebName = $dep->application()->ebName();
            $ebEnv = $dep->ebEnvironment();

            // skip invalid
            if (!$ebName || !$ebEnv) {
                continue;
            }

            // pre pop app -> environment list
            if (!isset($apps[$ebName])) {
                $apps[$ebName] = [];
            }

            $apps[$ebName][$ebEnv] = $dep;
        }

        return $apps;
    }

    /**
     * Build an EB Environment data model from raw AWS response.
     *
     * @param array $data
     *
     * @return Environment
     */
    private function buildEBEnvironment(array $data)
    {
        return new Environment([
            'id' =>     isset($data['EnvironmentId']) ? $data['EnvironmentId'] : '',
            'name' =>   isset($data['EnvironmentName']) ? $data['EnvironmentName'] : '',
            'status' => isset($data['Status']) ? $data['Status'] : '',
            'health' => isset($data['Health']) ? $data['Health'] : '',

            'applicationName' => isset($data['ApplicationName']) ? $data['ApplicationName'] : '',
            'currentVersion' =>  isset($data['VersionLabel']) ? $data['VersionLabel'] : '',
            'solution' =>        isset($data['SolutionStackName']) ? $data['SolutionStackName'] : '',
            'url' =>             isset($data['CNAME']) ? $data['CNAME'] : ''
        ]);
    }

    /**
     * @param string $method
     * @param array $params
     *
     * @return array
     */
    private function callService($method, array $params)
    {
        $callable = [$this->eb, $method];
        try {
            $response = $callable($params);
        } catch (AwsExceptionInterface $e) {
            return [];
        }

        return $response;
    }
}
