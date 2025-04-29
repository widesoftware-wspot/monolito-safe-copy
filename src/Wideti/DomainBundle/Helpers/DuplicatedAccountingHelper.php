<?php
namespace Wideti\DomainBundle\Helpers;

class DuplicatedAccountingHelper
{
    public static function clearKeys(array $array, array $keys = [])
    {
        if (count($keys) == 0) {
            $keys = ['username', 'callingstationid', 'framedipaddress'];
        }

        $acctFields = $array['_source'];
        foreach ($acctFields as $subKey => $subValue) {
            if (!in_array($subKey, $keys)) {
                unset($acctFields[$subKey]);
            }
        }

        return $acctFields;
    }

    public static function hasDifference(array $curr, array $next, array $keys = [])
    {
        $checkCurr = self::clearKeys($curr, $keys);
        $checkNext = self::clearKeys($next, $keys);

        if (count(array_diff($checkCurr, $checkNext)) === 0) {
            return true;
        }
        return false;
    }

    public static function failedOnClose(array $accounting)
    {
        $interimUpdate = date('Y-m-d H:i:s', strtotime($accounting['interim_update']));

        if ($interimUpdate < date('Y-m-d H:i:s', strtotime('-45 minutes'))) {
            return true;
        }
        return false;
    }
}
