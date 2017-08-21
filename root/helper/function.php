<?php
if (!function_exists('bubblingSort')) {
    /**
     * @param array $array
     * @return array
     * @throws \Exception
     */
    function bubblingSort(array $array)
    {
        $count = count($array);
        if (!$count) {
            throw new Exception("Cannot be an empty array!");
        }
        for ($i = 0; $i < $count; ++$i) {
            for ($ii = $i; $ii < $count; ++$ii) {
                if ($array[$i] > $array[$ii]) {
                    $temp = $array[$i];
                    $array[$i] = $array[$ii];
                    $array[$ii] = $temp;
                }
            }
        }
        return $array;
    }
}


if (!function_exists('runtime')) {
    function runtime():\root\helper\Runtime
    {
        return \root\helper\Runtime::getInstance();
    }
}