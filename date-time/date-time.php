<?php

defined('ABSPATH') || exit;

use Morilog\Jalali\Jalalian;

final class ANIPO_DATE_TIME
{
    private static $_instance = null;

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
    }

    public function date_and_time($timestamp = '')
    {
        if ($timestamp !== '') {
            // Convert the UTC timestamp to WordPress's timezone string
            $wp_local_time = get_date_from_gmt(gmdate('Y-m-d H:i:s', $timestamp), 'Y-m-d H:i:s');
            // Create a new DateTime object in WordPress timezone
            $datetime = new DateTime($wp_local_time);
            $dateAndTime = Jalalian::forge($datetime->getTimestamp())->format('Y/m/d H:i:s');
            $weekday = Jalalian::forge($datetime->getTimestamp())->format('l');
            $hour = Jalalian::forge($datetime->getTimestamp())->format('H');
            $minute = Jalalian::forge($datetime->getTimestamp())->format('i');
        } else {
            $dateAndTime = Jalalian::forge(current_time('timestamp'))->format('Y/m/d H:i:s');
            $weekday = Jalalian::forge(current_time('timestamp'))->format('l');
            $hour = Jalalian::forge(current_time('timestamp'))->format('H');
            $minute = Jalalian::forge(current_time('timestamp'))->format('i');
        }
        $dateAndTimeArray = explode(' ', $dateAndTime);
        $dateArray = explode("/", $dateAndTimeArray[0]);
        $year = intval($dateArray[0]);
        $month = $this->checkMonth(intval($dateArray[1]));
        $day = intval($dateArray[2]);
        return ["date" => $dateAndTimeArray[0], "time" => $dateAndTimeArray[1], "year" => $year,
            "month" => $month, "day" => $day, "hour" => $hour, "minute" => $minute, "weekday" => $weekday];
    }

    private function checkMonth($month)
    {
        $month = intval($month);
        $monthArray = ["فروردین", "اردیبهشت", "خرداد", "تیر", "مرداد", "شهریور", "مهر", "آبان", "آذر", "دی", "بهمن", "اسفند"];
        return $monthArray[$month - 1];
    }
}

return ANIPO_DATE_TIME::instance();
