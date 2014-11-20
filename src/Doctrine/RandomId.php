<?php

namespace QL\Hal\Doctrine;

/**
 * Class RandomId
 * @package QL\Hal\Doctrine
 */
class RandomId
{
    /**
     * @return string
     */
    public function __invoke()
    {
        return md5(rand());
    }
}