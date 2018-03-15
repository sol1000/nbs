<?php

class DateHelper {
  /**
   * Convert WeekNumber from 0:Sun - 6:Sat to 0:Mon - 6:Sun
   *
   * @param date $date
   *
   * @return short
   */
  public function getISOWeekNo($date) {
    return ((((date('w', strtotime($date)) - 1)%7)+7)%7);
  }

  /**
   * Get Monday of the week of date
   *
   * @param date $date
   *
   * @return date
   */
  public function getMondayOfTheWeek($date) {
    $weekno = self::getISOWeekNo($date);
    return date('Y-m-d', strtotime("$date - $weekno days"));
  }

  /**
   * Get Sunday of the week of date
   *
   * @param date $date
   *
   * @return date
   */
  public function getSundayOfTheWeek($date) {
    $weekno = self::getISOWeekNo($date);
    return date('Y-m-d', strtotime("$date + ".(6 - $weekno)." days"));
  }

  /**
   * @param date $start_date
   * @param date $end_date
   *
   * @return integer
   */
  public function countDays($start_date, $end_date) {
    return floor((strtotime($end_date) - strtotime($start_date))/86400) + 1;
  }

  /**
   * @param date $date
   *
   * @return string
   */
  public function getWeekCode($date) {
    $year = date('Y', strtotime($date));
    $month = date('n', strtotime($date));
    $ISO_week_no = date('W', strtotime($date));
    $year = ($month == 1 && intval($ISO_week_no) > 50)? $year - 1 : $year;
    $year = ($month == 12 && intval($ISO_week_no) <= 1)? $year + 1 : $year;

    return $year.'-W'.$ISO_week_no;
  }

  /**
   * @param integer $days
   *
   * @return date
   */
  public function convertDaysIntoDate($days) {
    return date('Y-m-d', strtotime('1970-01-01') + (intval($days) * 86400));
  }

  /**
   * @param date $date
   *
   * @return days
   */
  public function convertDateIntoDays($date) {
    return floor((strtotime($date) - strtotime('1970-01-01')) / 86400);
  }
}