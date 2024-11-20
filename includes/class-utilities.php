<?php

class Utilities
{
    /**
     * __construct function.
     *
     * @access public
     * @param mixed $action (default: null)
     * @return void
     */
    public function __construct()
    {
    }

    public static function getClosestMonday($d = "", $format = "m/d/Y")
    {
        if ($d == "")
            $d = date("m/d/Y");
        $delta = date("w", strtotime($d)) - 1;
        if ($delta < 0)
            $delta = 6;
        return date($format, mktime(0, 0, 0, date('m'), date('d') - $delta, date('Y')));
    }

    public static function getClosestSunday($d = "")
    {
        return date('D') !== 'Sun' ? date("m/d/Y", strtotime("Next Sunday")) : date("m/d/Y");
    }

    public static function convertDateToStandard($date, $offset = '')
    {
        $timestamp = $offset ? strtotime($offset, strtotime($date)) : strtotime($date);
        return date("c", $timestamp);
    }

    public static function convertDateToUserFriendly($date, $offset = false)
    {
        // $timestamp = $offset ? strtotime($offset, strtotime($date)) : strtotime($date) ;
        // return date("m/d/Y h:m A", $timestamp);
        if($date) {
            $date = $offset ? (new DateTime($date))->setTimezone(new DateTimeZone($offset)) : new DateTime($date);

            return $date->format('m/d/Y h:i A');
        }
    }

    public static function convertDateToEpoch($date, $offset = '')
    {
        return $offset ? strtotime($offset, strtotime($date)) : strtotime($date);
    }

    public static function toMoney($currencyCode, $amount)
    {
        $formattedAmount = number_format($amount, 2, '.', ',');
        return self::valueArray($amount, $currencyCode . '' . $formattedAmount);
    }

    public static function valueArray($value, $displayValue)
    {
        return array(
            'displayValue' => $displayValue,
            'value' => $value
        );
    }

    public static function toDateValueSpread($date)
    {
        return array(
            'raw' => $date,
            'object' => self::convertDateToStandard($date),
            'epoch' => self::convertDateToEpoch($date)
        );
    }

    public static function isTimestampWithinTwoDates($startDate, $timestamp, $endDate)
    {
        $isBetween = false;

        //var_dump("EPOCH TIMESTAMPS BEFORE:  $startDate - $timestamp - $endDate");  
        if ($timestamp && $startDate && $endDate) {
            $a = self::convertDateToEpoch($startDate);
            $b = self::convertDateToEpoch($timestamp);
            $c = self::convertDateToEpoch($endDate);

            //var_dump("EPOCH TIMESTAMPS:  $a - $b - $c");  
            if (($a <= $b) && ($b <= $c)) {
                $isBetween = true;
            }
        }

        return $isBetween;
    }

    public static function dateWithinSevenDays($expirationDate)
    {
        $numDays = (new DateTime($expirationDate))->diff(new DateTime('now'))->days;

        return $numDays <= 7 ? true : false;
    }

    public static function timeDifferenceInMinutes($time)
    {
        $minutes = '';

        if ($time) {
            $provided = (new DateTime($time))->setTimezone(new DateTimeZone('America/New_York'))->getTimestamp();
            $now = (new DateTime('now', new DateTimeZone('America/New_York')))->getTimestamp();

            $diff = $provided - $now;
            
            if($diff < 1){
                $minutes = 'Ready!';
            } else {
                $minutes = ceil((($diff / 1000) / 60));
            }
        }

        return $minutes;
    }

    public static function formatPhoneNumber($phone_number)
    {
        if($phone_number){
            if (!preg_match('/^(?:\+?(?<countrycode>\d{1,2}))?[- ]?(?:[(\[]?(?<areacode>\d{3})[)\]]?)?[- ]?(?:(?<first3>\d{3})[- ]?(?<last4>\d{4}))[- ]?(?:(?:[:;,]|e\w+| )+[ \t]*(?<ext>\d+))?$/', $phone_number, $parts))
                return preg_replace('/[^0-9]/', '', $phone_number);

            $phone = [
                "country_code" => !empty($parts["countrycode"]) ? "+{$parts["countrycode"]}" : null,
                "area_code" => !empty($parts["areacode"]) ? "({$parts["areacode"]})" : null,
                "body" => "{$parts["first3"]}-{$parts["last4"]}",
                "extension" => !empty($parts["ext"]) ? "ext. {$parts["ext"]}" : null,
            ];

            return implode(" ", array_filter($phone));
        }
    }
}
