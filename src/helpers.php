<?php

if (! function_exists('invert_and_nullify')) {
    /**
     * Use array values as keys and
     * set all values to null.
     *
     * @param  array $array
     * @return array|false
     */
    function invert_and_nullify(array $array)
    {
        return array_combine(
            $array,
            array_fill(0, count($array), null)
        );
    }
}
