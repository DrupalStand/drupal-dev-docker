<?php

namespace Drupal\calendar;

/**
 * Defines a calendar date info object.
 */
class CalendarDateInfo {

  /**
   * The calendar type.
   *
   * @var string
   *   The type of calendar.
   */
  protected $calendarType;

  /**
   * The date argument.
   *
   * @var \Drupal\calendar_datetime\Plugin\views\argument\Date $dateArgument
   *   The date argument.
   */
  protected $dateArgument;

  /**
   * The position of the date argument among the other view arguments.
   *
   * @var int dateArgumentPosition
   *   The date argument position.
   */
  protected $dateArgumentPosition;

  /**
   * The timezone information for this calendar.
   *
   * @var \DateTimeZone
   *   The timezone object.
   */
  protected $timezone;

  /**
   * The granularity of this calendar.
   *
   * @var string
   *   The granularity of this calendar (e.g. 'day', 'week').
   */
  protected $granularity;

  /**
   * The range of this calendar.
   *
   * @var string
   *   The range of this calendar (e.g. '-3:+3').
   */
  protected $range;

  // @TODO Find a better way to hold all "minimum x" information

  /**
   * The minimum date of this calendar.
   *
   * @var \DateTime
   *   The minimum date of this calendar.
   */
  protected $minDate;

  /**
   * The minimum year of this calendar.
   *
   * @var string
   *   The minimum year of this calendar.
   */
  protected $minYear;

  /**
   * The minimum month of this calendar.
   *
   * @var string
   *   The minimum month of this calendar.
   */
  protected $minMonth;

  /**
   * The minimum day of this calendar.
   *
   * @var string
   *   The minimum day of this calendar.
   */
  protected $minDay;

  /**
   * The minimum week number of this calendar.
   *
   * @var int
   *   The minimum week number of this calendar.
   */
  protected $minWeek;

  /**
   * The maximum date of this calendar.
   *
   * @var \DateTime
   *   The maximum date.
   */
  protected $maxDate;

  /**
   * @TODO explain what this variable does.
   *
   * @var boolean
   *   The forbid value.
   */
  protected $forbid;

  /**
   * Getter for the calendar type.
   *
   * @return string
   *   The calendar type.
   */
  public function getCalendarType() {
    return $this->calendarType;
  }

  /**
   * Setter for the calendar type.
   *
   * @param string $calendarType
   *   The calendar type.
   */
  public function setCalendarType($calendarType) {
    $this->calendarType = $calendarType;
  }

  /**
   * Getter for the date argument.
   *
   * @return \Drupal\calendar_datetime\Plugin\views\argument\Date
   *   The date argument.
   */
  public function getDateArgument() {
    return $this->dateArgument;
  }

  /**
   * Setter for the date argument.
   *
   * @param \Drupal\calendar_datetime\Plugin\views\argument\Date $dateArgument
   *   The date argument.
   */
  public function setDateArgument($dateArgument) {
    $this->dateArgument = $dateArgument;
  }

  /**
   * Getter for the date argument position.
   *
   * @return int
   *   The date argument position.
   */
  public function getDateArgumentPosition() {
    return $this->dateArgumentPosition;
  }

  /**
   * Setter for the date argument position.
   *
   * @param int $dateArgumentPosition
   *   The date argument position.
   */
  public function setDateArgumentPosition($dateArgumentPosition) {
    $this->dateArgumentPosition = $dateArgumentPosition;
  }

  /**
   * Getter for the timezone variable.
   *
   * @return \DateTimeZone
   *   The timezone variable.
   */
  public function getTimezone() {
    return $this->timezone;
  }

  /**
   * Setter for the timezone variable.
   *
   * @param \DateTimeZone $timezone
   *   The timezone variable.
   */
  public function setTimezone($timezone) {
    $this->timezone = $timezone;
  }

  /**
   * Getter for the calendar granularity.
   *
   * @return string
   *   The calendar granularity.
   */
  public function getGranularity() {
    return $this->granularity;
  }

  /**
   * Setter for the granularity.
   *
   * @param string $granularity
   *   The calendar granularity.
   */
  public function setGranularity($granularity) {
    $this->granularity = $granularity;
  }

  /**
   * Getter for the range.
   *
   * @return string
   *   The calendar range.
   */
  public function getRange() {
    return $this->range;
  }

  /**
   * Setter for the range.
   *
   * @param string $range
   *   The calendar range.
   */
  public function setRange($range) {
    $this->range = $range;
  }

  /**
   * Getter for the minimum date.
   *
   * @return \DateTime
   *   The minimum date.
   */
  public function getMinDate() {
    return $this->minDate;
  }

  /**
   * Setter for the minimum date.
   *
   * @param \DateTime $minDate
   *   The minimum date.
   */
  public function setMinDate($minDate) {
    $this->minDate = $minDate;
  }

  /**
   * Getter for the minimum year.
   *
   * @return string
   *   The minimum year.
   */
  public function getMinYear() {
    return $this->minYear;
  }

  /**
   * Setter for the minimum year.
   *
   * @param string $minYear
   *   The minimum year.
   */
  public function setMinYear($minYear) {
    $this->minYear = $minYear;
  }

  /**
   * Getter for the minimum month.
   *
   * @return string
   *   The minimum month.
   */
  public function getMinMonth() {
    return $this->minMonth;
  }

  /**
   * Setter for the minimum month.
   *
   * @param string $minMonth
   *   The minimum month.
   */
  public function setMinMonth($minMonth) {
    $this->minMonth = $minMonth;
  }

  /**
   * Getter for the minimum day.
   *
   * @return string
   *   The minimum day.
   */
  public function getMinDay() {
    return $this->minDay;
  }

  /**
   * Setter for the minimum day.
   *
   * @param string $minDay
   *   The minimum day.
   */
  public function setMinDay($minDay) {
    $this->minDay = $minDay;
  }

  /**
   * Getter for the minimum week number.
   *
   * @return int
   *   The minimum week number.
   */
  public function getMinWeek() {
    return $this->minWeek;
  }

  /**
   * Setter for the minimum week number.
   *
   * @param int $minWeek
   *   The minimum week number.
   */
  public function setMinWeek($minWeek) {
    $this->minWeek = $minWeek;
  }

  /**
   * Getter for the maximum date.
   *
   * @return \DateTime
   *   The maximum date.
   */
  public function getMaxDate() {
    return $this->maxDate;
  }

  /**
   * Setter for the maximum date.
   *
   * @param \DateTime $maxDate
   *   The maximum date.
   */
  public function setMaxDate($maxDate) {
    $this->maxDate = $maxDate;
  }

  /**
   * Getter for the forbid value of this calendar.
   *
   * @return boolean
   *   The forbid value.
   */
  public function isForbid() {
    return $this->forbid;
  }

  /**
   * Setter for the forbid value of this calendar.
   *
   * @param boolean $forbid
   *   The forbid value.
   */
  public function setForbid($forbid) {
    $this->forbid = $forbid;
  }
}
