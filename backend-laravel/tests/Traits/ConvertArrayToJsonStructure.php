<?php

namespace Tests\Traits;

trait ConvertArrayToJsonStructure
{
    /**
     * Recursively converts an array into a simplified JSON structure representation
     * for assertJsonStructure()
     *
     * @param  array<array-key, mixed>  $array
     * @return array<int|string, mixed>
     */
    protected static function convertArrayToJsonStructure(array $array): array
    {
        $structure = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (is_int($key)) {
                    $structure['*'] = self::convertArrayToJsonStructure($value);
                    break;
                } else {
                    $structure[$key] = self::convertArrayToJsonStructure($value);
                }
            } else {
                if (is_int($key)) {
                    $structure[] = $value;
                } else {
                    $structure[] = $key;
                }
            }
        }

        return $structure;
    }
}
