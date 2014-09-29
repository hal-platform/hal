<?php
namespace QL\Hal\Helpers;

/**
 * Class DirectoryHelper
 *
 * @author Matt Colf <matthewcolf@quickenloans.com>
 */
class DirectoryHelper
{
    private $root;

    /**
     * @param $root
     */
    public function __construct(
        $root
    ) {
        $this->root = $root;
    }

    /**
     * Get the full filesystem path for a path relative to the the project root
     *
     * @param $path
     * @return string
     */
    public function fullPath($path)
    {
        return sprintf(
            '%s%s%s',
            rtrim($this->root, DIRECTORY_SEPARATOR),
            DIRECTORY_SEPARATOR,
            ltrim($path, DIRECTORY_SEPARATOR)
        );
    }
}
