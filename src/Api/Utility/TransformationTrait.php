<?php

namespace QL\Hal\Api\Utility;

/**
 * Array Transformation Trait
 */
trait TransformationTrait
{
    /**
     * Transform an array of data by applying functions to known keys
     *
     * @param array $data
     * @param array $mappers
     * @return array
     */
    private function transform(array $data, array $mappers)
    {
        foreach ($data as $key => &$value) {
            foreach ($mappers as $indicator => $mapper) {
                if ($key == $indicator) {
                    if ($mapper === null) {
                        // remove the item when the mapper is null
                        unset($data[$key]);
                    } else {
                        $value = call_user_func($mapper, $value);
                    }
                }
            }
        }

        return $data;
    }
}