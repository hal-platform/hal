<?php

namespace QL\Hal\Api;

use InvalidArgumentException;
use MCP\DataType\HttpUrl;
use MCP\DataType\Time\TimePoint;
use QL\Hal\Api\Normalizer\HttpUrlNormalizer;
use QL\Hal\Api\Normalizer\TimePointNormalizer;
use QL\Hal\Api\Normalizer\ApplicationNormalizer;
use QL\Hal\Api\Normalizer\BuildNormalizer;
use QL\Hal\Api\Normalizer\DeploymentNormalizer;
use QL\Hal\Api\Normalizer\EnvironmentNormalizer;
use QL\Hal\Api\Normalizer\EventLogNormalizer;
use QL\Hal\Api\Normalizer\GroupNormalizer;
use QL\Hal\Api\Normalizer\PushNormalizer;
use QL\Hal\Api\Normalizer\ServerNormalizer;
use QL\Hal\Api\Normalizer\UserNormalizer;
use QL\Hal\Core\Entity\Deployment;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Entity\EventLog;
use QL\Hal\Core\Entity\Group;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\Entity\Repository;
use QL\Hal\Core\Entity\Server;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\Build;

/**
 * Object Normalizer
 */
class Normalizer
{
    const UNKNOWN_OBJECT = 'Unable to normalize unknown type %s.';

    /**
     * @var BuildNormalizer
     */
    private $builds;

    /**
     * @var DeploymentNormalizer
     */
    private $deployments;

    /**
     * @var EnvironmentNormalizer
     */
    private $enviroments;

    /**
     * @var EventLogNormalizer
     */
    private $events;

    /**
     * @var GroupNormalizer
     */
    private $groups;

    /**
     * @var PushNormalizer
     */
    private $pushes;

    /**
     * @var ApplicationNormalizer
     */
    private $repositories;

    /**
     * @var ServerNormalizer
     */
    private $servers;

    /**
     * @var UserNormalizer
     */
    private $users;

    /**
     * @var TimePointNormalizer
     */
    private $time;

    /**
     * @var HttpUrlNormalizer
     */
    private $url;

    /**
     * @param BuildNormalizer $builds
     * @param DeploymentNormalizer $deployments
     * @param EnvironmentNormalizer $environments
     * @param EventLogNormalizer $events
     * @param GroupNormalizer $groups
     * @param PushNormalizer $pushes
     * @param ApplicationNormalizer $repositories
     * @param ServerNormalizer $servers
     * @param UserNormalizer $users
     * @param TimePointNormalizer $time
     * @param HttpUrlNormalizer $url
     */
    public function __construct(
        BuildNormalizer $builds,
        DeploymentNormalizer $deployments,
        EnvironmentNormalizer $environments,
        EventLogNormalizer $events,
        GroupNormalizer $groups,
        PushNormalizer $pushes,
        ApplicationNormalizer $repositories,
        ServerNormalizer $servers,
        UserNormalizer $users,
        TimePointNormalizer $time,
        HttpUrlNormalizer $url
    ) {
        $this->builds = $builds;
        $this->deployments = $deployments;
        $this->enviroments = $environments;
        $this->events = $events;
        $this->groups = $groups;
        $this->pushes = $pushes;
        $this->repositories = $repositories;
        $this->servers = $servers;
        $this->users = $users;
        $this->time = $time;
        $this->url = $url;
    }

    /**
     * Normalize all known object types
     *
     * @param $input
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function normalize($input)
    {
        if (is_array($input)) {
            return array_map(function ($item) {
                return $this->normalize($item);
            }, $input);
        }

        switch (true) {
            case $input instanceof Build:
                return $this->resolve($this->builds->resource($input));
            case $input instanceof Deployment:
                return $this->resolve($this->deployments->resource($input));
            case $input instanceof Environment:
                return $this->resolve($this->enviroments->resource($input));
            case $input instanceof EventLog:
                return $this->resolve($this->events->resource($input));
            case $input instanceof Group:
                return $this->resolve($this->groups->resource($input));
            case $input instanceof Push:
                return $this->resolve($this->pushes->resource($input));
            case $input instanceof Repository:
                return $this->resolve($this->repositories->resource($input));
            case $input instanceof Server:
                return $this->resolve($this->servers->resource($input));
            case $input instanceof User:
                return $this->resolve($this->users->resource($input));
            case $input instanceof TimePoint:
                return $this->time->normalize($input);
            case $input instanceof HttpUrl:
                return $this->url->normalize($input);
            case is_null($input):
                return null;
        }

        $type = (is_object($input)) ? get_class($input) : sprintf('%s(%s)', gettype($input), $input);

        throw new InvalidArgumentException(sprintf(self::UNKNOWN_OBJECT, $type));
    }

    /**
     * Recursively resolve any objects in the tree of normalized values
     *
     * @param array $tree
     * @return array
     */
    public function resolve(array $tree)
    {
        array_walk_recursive($tree, function (&$leaf) {
            if (is_object($leaf)) {
                $leaf = $this->normalize($leaf);
            }
        });

        return $tree;
    }
}
