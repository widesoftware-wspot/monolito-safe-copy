<?php

namespace Wideti\DomainBundle\Helpers;

class CsvHelper
{
    private function __construct()
    {
    }

    public static function getFileDelimiter($file, $checkLines = 2)
    {
        $file = new \SplFileObject($file);
        $delimiters = [
            ',',
            '\t',
            ';',
            '|',
            ':'
        ];
        $results = [];
        $i = 0;
        while ($file->valid() && $i <= $checkLines) {
            $line = $file->fgets();
            foreach ($delimiters as $delimiter) {
                $regExp = '/['.$delimiter.']/';
                $fields = preg_split($regExp, $line);
                if (count($fields) > 1) {
                    if (!empty($results[$delimiter])) {
                        $results[$delimiter]++;
                    } else {
                        $results[$delimiter] = 1;
                    }
                }
            }
            $i++;
        }
        $results = array_keys($results, max($results));
        return $results[0];
    }

    /**
     * @param array $expected
     * @param array $header
     * @return bool
     */
    public static function isValidHeader(array $expected, array $header)
    {
        foreach ($header as $key => $value) {
            if (strtolower($expected[$key]) != strtolower($value)) {
                return false;
            }
        }
        return true;
    }
}
